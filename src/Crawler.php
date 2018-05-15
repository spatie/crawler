<?php

namespace Spatie\Crawler;

use Generator;
use Spatie\Crawler\Handlers\CrawlRequestFailed;
use Spatie\Crawler\Handlers\CrawlRequestFulfilled;
use Tree\Node\Node;
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Request;
use Spatie\Robots\RobotsTxt;
use InvalidArgumentException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\UriInterface;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\DomCrawler\Link;
use Spatie\Crawler\CrawlQueue\CollectionCrawlQueue;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

class Crawler
{
    use CrawlerProperties;

    /** @var \GuzzleHttp\Client */
    protected $client;

    /** @var \Psr\Http\Message\UriInterface */
    protected $baseUrl;

    /** @var array[\Spatie\Crawler\CrawlObserver] */
    protected $crawlObservers;

    /** @var \Spatie\Crawler\CrawlProfile */
    protected $crawlProfile;

    /** @var int */
    protected $concurrency;

    /** @var \Spatie\Crawler\CrawlQueue\CrawlQueue */
    protected $crawlQueue;

    /** @var int */
    protected $crawledUrlCount = 0;

    /** @var int|null */
    protected $maximumCrawlCount = null;

    /** @var int */
    protected $maximumResponseSize = 1024 * 1024 * 2;

    /** @var int|null */
    protected $maximumDepth = null;

    /** @var bool */
    protected $respectRobots = true;

    /** @var \Tree\Node\Node */
    protected $depthTree;

    /** @var bool */
    protected $executeJavaScript = false;

    /** @var Browsershot */
    protected $browsershot = null;

    /** @var \Spatie\Robots\RobotsTxt */
    protected $robotsTxt = null;

    protected static $defaultClientOptions = [
        RequestOptions::COOKIES => true,
        RequestOptions::CONNECT_TIMEOUT => 10,
        RequestOptions::TIMEOUT => 10,
        RequestOptions::ALLOW_REDIRECTS => false,
    ];

    /**
     * @param array $clientOptions
     *
     * @return static
     */
    public static function create(array $clientOptions = [])
    {
        $clientOptions = (count($clientOptions))
            ? $clientOptions
            : self::$defaultClientOptions;

        $client = new Client($clientOptions);

        return new static($client);
    }

    public function __construct(Client $client, int $concurrency = 10)
    {
        $this->client = $client;

        $this->concurrency = $concurrency;

        $this->crawlProfile = new CrawlAllUrls();

        $this->crawlQueue = new CollectionCrawlQueue();
    }

    /**
     * @param \Psr\Http\Message\UriInterface|string $baseUrl
     */
    public function startCrawling($baseUrl)
    {
        if (! $baseUrl instanceof UriInterface) {
            $baseUrl = new Uri($baseUrl);
        }

        if ($baseUrl->getScheme() === '') {
            $baseUrl = $baseUrl->withScheme('http');
        }

        if ($baseUrl->getPath() === '') {
            $baseUrl = $baseUrl->withPath('/');
        }

        $this->baseUrl = $baseUrl;

        $crawlUrl = CrawlUrl::create($this->baseUrl);

        $this->robotsTxt = $this->createRobotsTxt($crawlUrl->url);

        if ($this->robotsTxt->allows((string) $crawlUrl->url)) {
            $this->addToCrawlQueue($crawlUrl);
        }

        $this->depthTree = new Node((string) $this->baseUrl);

        $this->startCrawlingQueue();

        foreach ($this->crawlObservers as $crawlObserver) {
            $crawlObserver->finishedCrawling();
        }
    }

    protected function startCrawlingQueue()
    {
        $fulfilledHandler = $this->getFulfilledHandler();

        $failedHandler= $this->getFailedHandler();

        while ($this->crawlQueue->hasPendingUrls()) {
            $pool = new Pool($this->client, $this->getCrawlRequests(), [
                'concurrency' => $this->concurrency,
                'options' => $this->client->getConfig(),
                'fulfilled' => $fulfilledHandler,
                'rejected' => $failedHandler,
            ]);

            $promise = $pool->promise();

            $promise->wait();
        }
    }

    public function endsWith($haystack, $needle)
    {
        return strrpos($haystack, $needle) + strlen($needle) ===
            strlen($haystack);
    }

    protected function createRobotsTxt(UriInterface $uri): RobotsTxt
    {
        return RobotsTxt::create($uri->withPath('/robots.txt'));
    }

    protected function getCrawlRequests(): Generator
    {
        while ($crawlUrl = $this->crawlQueue->getFirstPendingUrl()) {
            if (! $this->crawlProfile->shouldCrawl($crawlUrl->url)) {
                $this->crawlQueue->markAsProcessed($crawlUrl);
                continue;
            }

            if ($this->crawlQueue->hasAlreadyBeenProcessed($crawlUrl)) {
                continue;
            }

            foreach ($this->crawlObservers as $crawlObserver) {
                $crawlObserver->willCrawl($crawlUrl->url);
            }

            $this->crawlQueue->markAsProcessed($crawlUrl);

            yield $crawlUrl->getId() => new Request('GET', $crawlUrl->url);
        }
    }

    public function addAllLinksToCrawlQueue(string $html, UriInterface $foundOnUrl)
    {
        $allLinks = $this->extractAllLinks($html, $foundOnUrl);

        collect($allLinks)
            ->filter(function (UriInterface $url) {
                return $this->hasCrawlableScheme($url);
            })
            ->map(function (UriInterface $url) {
                return $this->normalizeUrl($url);
            })
            ->filter(function (UriInterface $url) {
                return $this->crawlProfile->shouldCrawl($url);
            })
            ->reject(function (UriInterface $url) {
                return $this->crawlQueue->has($url);
            })
            ->each(function (UriInterface $url) use ($foundOnUrl) {
                $node = $this->addToDepthTree($this->depthTree, $url, $foundOnUrl);

                if (strpos($url->getPath(), '/tel:') === 0) {
                    return;
                }

                if (! $this->shouldCrawl($node)) {
                    return;
                }

                if ($this->maximumCrawlCountReached()) {
                    return;
                }

                $crawlUrl = CrawlUrl::create($url, $foundOnUrl);

                $this->addToCrawlQueue($crawlUrl);
            });
    }

    protected function shouldCrawl(Node $node): bool
    {
        if ($this->respectRobots) {
            return $this->robotsTxt->allows($node->getValue());
        }

        if (is_null($this->maximumDepth)) {
            return true;
        }

        return $node->getDepth() <= $this->maximumDepth;
    }

    /**
     * @param string                         $html
     * @param \Psr\Http\Message\UriInterface $foundOnUrl
     *
     * @return \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection|null
     */
    protected function extractAllLinks(string $html, UriInterface $foundOnUrl)
    {
        if ($this->executeJavaScript) {
            $html = $this->getBodyAfterExecutingJavaScript($foundOnUrl);
        }

        $domCrawler = new DomCrawler($html, $foundOnUrl);

        return collect($domCrawler->filterXpath('//a')->links())
            ->reject(function (Link $link) {
                return $link->getNode()->getAttribute('rel') === 'nofollow';
            })
            ->map(function (Link $link) {
                try {
                    return new Uri($link->getUri());
                } catch (InvalidArgumentException $exception) {
                    return;
                }
            })
            ->filter();
    }

    protected function normalizeUrl(UriInterface $url): UriInterface
    {
        return $url->withFragment('');
    }

    protected function hasCrawlableScheme(UriInterface $uri): bool
    {
        return in_array($uri->getScheme(), ['http', 'https']);
    }

    protected function addToDepthTree(Node $node, UriInterface $url, UriInterface $parentUrl)
    {
        $returnNode = null;

        if ($node->getValue() === (string) $parentUrl) {
            $newNode = new Node((string) $url);

            $node->addChild($newNode);

            return $newNode;
        }

        foreach ($node->getChildren() as $currentNode) {
            $returnNode = $this->addToDepthTree($currentNode, $url, $parentUrl);

            if (! is_null($returnNode)) {
                break;
            }
        }

        return $returnNode;
    }

    protected function getBodyAfterExecutingJavaScript(UriInterface $foundOnUrl): string
    {
        $browsershot = $this->getBrowsershot();

        $html = $browsershot->setUrl((string) $foundOnUrl)->bodyHtml();

        return html_entity_decode($html);
    }

    protected function getBrowsershot(): Browsershot
    {
        if (! $this->browsershot) {
            $this->browsershot = new Browsershot();
        }

        return $this->browsershot;
    }

    public function setBrowsershot(Browsershot $browsershot)
    {
        $this->browsershot = $browsershot;

        return $this;
    }

    protected function addToCrawlQueue(CrawlUrl $crawlUrl)
    {
        $this->crawledUrlCount++;

        $this->crawlQueue->add($crawlUrl);

        return $this;
    }

    protected function maximumCrawlCountReached(): bool
    {
        if (is_null($this->maximumCrawlCount)) {
            return false;
        }

        return $this->crawledUrlCount >= $this->maximumCrawlCount;
    }

    protected function getFulfilledHandler(): CrawlRequestFulfilled
    {
        return new CrawlRequestFulfilled($this);
    }

    protected function getFailedHandler(): CrawlRequestFailed
    {
        return new CrawlRequestFailed($this);
    }
}
