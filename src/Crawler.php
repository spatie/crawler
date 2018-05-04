<?php

namespace Spatie\Crawler;

use Generator;
use Tree\Node\Node;
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Request;
use Spatie\Robots\RobotsTxt;
use InvalidArgumentException;
use Spatie\Robots\RobotsMeta;
use GuzzleHttp\RequestOptions;
use Spatie\Robots\RobotsHeaders;
use Psr\Http\Message\UriInterface;
use Spatie\Browsershot\Browsershot;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\DomCrawler\Link;
use Psr\Http\Message\ResponseInterface;
use Spatie\Crawler\CrawlQueue\CrawlQueue;
use GuzzleHttp\Exception\RequestException;
use Spatie\Crawler\CrawlQueue\CollectionCrawlQueue;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

class Crawler
{
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
    protected $ignoreRobots = false;

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
     * @param int $concurrency
     *
     * @return $this
     */
    public function setConcurrency(int $concurrency)
    {
        $this->concurrency = $concurrency;

        return $this;
    }

    /**
     * Responses that are larger that then specified value will be ignored.
     *
     * @param int $maximumResponseSizeInBytes
     *
     * @return $this
     */
    public function setMaximumResponseSize(int $maximumResponseSizeInBytes)
    {
        $this->maximumResponseSize = $maximumResponseSizeInBytes;

        return $this;
    }

    /**
     * @param int $maximumCrawlCount
     *
     * @return $this
     */
    public function setMaximumCrawlCount(int $maximumCrawlCount)
    {
        $this->maximumCrawlCount = $maximumCrawlCount;

        return $this;
    }

    /**
     * @param int $maximumDepth
     *
     * @return $this
     */
    public function setMaximumDepth(int $maximumDepth)
    {
        $this->maximumDepth = $maximumDepth;

        return $this;
    }

    /**
     * @param bool $ignoreRobots
     *
     * @return $this
     */
    public function ignoreRobots(bool $ignoreRobots = true)
    {
        $this->ignoreRobots = $ignoreRobots;

        return $this;
    }

    /**
     * @param CrawlQueue $crawlQueue
     *
     * @return $this
     */
    public function setCrawlQueue(CrawlQueue $crawlQueue)
    {
        $this->crawlQueue = $crawlQueue;

        return $this;
    }

    /**
     * @return $this
     */
    public function executeJavaScript()
    {
        $this->executeJavaScript = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function doNotExecuteJavaScript()
    {
        $this->executeJavaScript = false;

        return $this;
    }

    /**
     * @param \Spatie\Crawler\CrawlObserver|array[\Spatie\Crawler\CrawlObserver] $crawlObservers
     *
     * @return $this
     */
    public function setCrawlObserver($crawlObservers)
    {
        if (! is_array($crawlObservers)) {
            $crawlObservers = [$crawlObservers];
        }

        return $this->setCrawlObservers($crawlObservers);
    }

    public function setCrawlObservers(array $crawlObservers)
    {
        $this->crawlObservers = $crawlObservers;

        return $this;
    }

    public function addCrawlObserver(CrawlObserver $crawlObserver)
    {
        $this->crawlObservers[] = $crawlObserver;

        return $this;
    }

    /**
     * @param \Spatie\Crawler\CrawlProfile $crawlProfile
     *
     * @return $this
     */
    public function setCrawlProfile(CrawlProfile $crawlProfile)
    {
        $this->crawlProfile = $crawlProfile;

        return $this;
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

        $this->addToCrawlQueue($crawlUrl);

        $this->depthTree = new Node((string) $this->baseUrl);

        $this->startCrawlingQueue();

        foreach ($this->crawlObservers as $crawlObserver) {
            $crawlObserver->finishedCrawling();
        }
    }

    protected function startCrawlingQueue()
    {
        while ($this->crawlQueue->hasPendingUrls()) {
            $pool = new Pool($this->client, $this->getCrawlRequests(), [
                'concurrency' => $this->concurrency,
                'options' => $this->client->getConfig(),
                'fulfilled' => function (ResponseInterface $response, $index) {
                    $crawlUrl = $this->crawlQueue->getUrlById($index);

                    $body = $this->convertBodyToString($response->getBody(), $this->maximumResponseSize);

                    $robotsHeaders = RobotsHeaders::create($response->getHeaders());

                    $robotsMeta = RobotsMeta::create($body);

                    if (! $this->mayIndex($robotsHeaders, $robotsMeta)) {
                        return;
                    }

                    $this->handleCrawled($response, $crawlUrl);

                    if (! $this->crawlProfile instanceof CrawlSubdomains) {
                        if ($crawlUrl->url->getHost() !== $this->baseUrl->getHost()) {
                            return;
                        }
                    }

                    if (! $this->mayFollow($robotsHeaders, $robotsMeta)) {
                        return;
                    }

                    $this->addAllLinksToCrawlQueue(
                        $body,
                        $crawlUrl->url
                    );
                },
                'rejected' => function (RequestException $exception, $index) {
                    $this->handleCrawlFailed(
                        $exception,
                        $this->crawlQueue->getUrlById($index),
                        $exception
                    );
                },
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

    protected function convertBodyToString(StreamInterface $bodyStream, $readMaximumBytes = 1024 * 1024 * 2): string
    {
        $bodyStream->rewind();

        $body = $bodyStream->read($readMaximumBytes);

        return $body;
    }

    protected function createRobotsTxt(UriInterface $uri): RobotsTxt
    {
        return RobotsTxt::create($uri->withPath('/robots.txt'));
    }

    /**
     * @param ResponseInterface|null $response
     * @param CrawlUrl               $crawlUrl
     */
    protected function handleCrawled(ResponseInterface $response, CrawlUrl $crawlUrl)
    {
        foreach ($this->crawlObservers as $crawlObserver) {
            $crawlObserver->crawled(
                $crawlUrl->url,
                $response,
                $crawlUrl->foundOnUrl
            );
        }
    }

    /**
     * @param RequestException $exception
     * @param CrawlUrl         $crawlUrl
     */
    protected function handleCrawlFailed(RequestException $exception, CrawlUrl $crawlUrl)
    {
        foreach ($this->crawlObservers as $crawlObserver) {
            $crawlObserver->crawlFailed(
                $crawlUrl->url,
                $exception,
                $crawlUrl->foundOnUrl
            );
        }
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

    protected function addAllLinksToCrawlQueue(string $html, UriInterface $foundOnUrl)
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
                $node = $this->addtoDepthTree($this->depthTree, $url, $foundOnUrl);

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
        if (! $this->ignoreRobots) {
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

    protected function addtoDepthTree(Node $node, UriInterface $url, UriInterface $parentUrl)
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
        if ($this->browsershot) {
            return $this->browsershot;
        }

        $this->browsershot = new Browsershot();

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

    protected function mayIndex(RobotsHeaders  $robotsHeaders, RobotsMeta $robotsMeta): bool
    {
        if ($this->ignoreRobots) {
            return true;
        }

        if (! $robotsHeaders->mayIndex()) {
            return false;
        }

        if (! $robotsMeta->mayIndex()) {
            return false;
        }

        return true;
    }

    protected function mayFollow(RobotsHeaders $robotsHeaders, RobotsMeta $robotsMeta): bool
    {
        if ($this->ignoreRobots) {
            return true;
        }

        if (! $robotsHeaders->mayFollow()) {
            return false;
        }

        if (! $robotsMeta->mayFollow()) {
            return false;
        }

        return true;
    }
}
