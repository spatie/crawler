<?php

namespace Spatie\Crawler;

use Generator;
use Tree\Node\Node;
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Request;
use Spatie\Robots\RobotsTxt;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\UriInterface;
use Spatie\Browsershot\Browsershot;
use Spatie\Crawler\Handlers\CrawlRequestFailed;
use Spatie\Crawler\Handlers\CrawlRequestFulfilled;
use Spatie\Crawler\CrawlQueue\CollectionCrawlQueue;

class Crawler
{
    use CrawlerProperties;

    /** @var \GuzzleHttp\Client */
    protected $client;

    /** @var \Psr\Http\Message\UriInterface */
    protected $baseUrl;

    /** @var \Spatie\Crawler\CrawlObserverCollection */
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
            : static::$defaultClientOptions;

        $client = new Client($clientOptions);

        return new static($client);
    }

    public function __construct(Client $client, int $concurrency = 10)
    {
        $this->client = $client;

        $this->concurrency = $concurrency;

        $this->crawlProfile = new CrawlAllUrls();

        $this->crawlQueue = new CollectionCrawlQueue();

        $this->crawlObservers = new CrawlObserverCollection();
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

    public function addToDepthTree(UriInterface $url, UriInterface $parentUrl, Node $node = null): ?Node
    {
        $node = $node ?? $this->depthTree;

        $returnNode = null;

        if ($node->getValue() === (string) $parentUrl) {
            $newNode = new Node((string) $url);

            $node->addChild($newNode);

            return $newNode;
        }

        foreach ($node->getChildren() as $currentNode) {
            $returnNode = $this->addToDepthTree($url, $parentUrl, $currentNode);

            if (! is_null($returnNode)) {
                break;
            }
        }

        return $returnNode;
    }

    protected function startCrawlingQueue()
    {
        while ($this->crawlQueue->hasPendingUrls()) {
            $pool = new Pool($this->client, $this->getCrawlRequests(), [
                'concurrency' => $this->concurrency,
                'options' => $this->client->getConfig(),
                'fulfilled' => new CrawlRequestFulfilled($this),
                'rejected' => new CrawlRequestFailed($this),
            ]);

            $promise = $pool->promise();

            $promise->wait();
        }
    }

    /**
     * @deprecated This function will be removed in the next major version
     */
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

    public function addToCrawlQueue(CrawlUrl $crawlUrl): self
    {
        if (! $this->getCrawlProfile()->shouldCrawl($crawlUrl->url)) {
            return $this;
        }

        if ($this->getCrawlQueue()->has($crawlUrl->url)) {
            return $this;
        }

        $this->crawledUrlCount++;

        $this->crawlQueue->add($crawlUrl);

        return $this;
    }

    public function maximumCrawlCountReached(): bool
    {
        $maximumCrawlCount = $this->getMaximumCrawlCount();

        if (is_null($maximumCrawlCount)) {
            return false;
        }

        return $this->getCrawlerUrlCount() >= $maximumCrawlCount;
    }
}
