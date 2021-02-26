<?php

namespace Spatie\Crawler;

use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\UriInterface;
use Spatie\Browsershot\Browsershot;
use Spatie\Crawler\CrawlObservers\CrawlObserver;
use Spatie\Crawler\CrawlObservers\CrawlObserverCollection;
use Spatie\Crawler\CrawlProfiles\CrawlAllUrls;
use Spatie\Crawler\CrawlProfiles\CrawlProfile;
use Spatie\Crawler\CrawlQueues\ArrayCrawlQueue;
use Spatie\Crawler\CrawlQueues\CrawlQueue;
use Spatie\Crawler\Exceptions\InvalidCrawlRequestHandler;
use Spatie\Crawler\Handlers\CrawlRequestFailed;
use Spatie\Crawler\Handlers\CrawlRequestFulfilled;
use Spatie\Robots\RobotsTxt;
use Tree\Node\Node;

class Crawler
{
    public const DEFAULT_USER_AGENT = '*';

    protected Client $client;

    protected UriInterface $baseUrl;

    protected CrawlObserverCollection $crawlObservers;

    protected CrawlProfile $crawlProfile;

    protected int $concurrency;

    protected CrawlQueue $crawlQueue;

    protected int $totalUrlCount = 0;

    protected int $currentUrlCount = 0;

    protected ?int $totalCrawlLimit = null;

    protected ?int $currentCrawlLimit = null;

    protected int $maximumResponseSize = 1024 * 1024 * 2;

    protected ?int $maximumDepth = null;

    protected bool $respectRobots = true;

    protected bool $rejectNofollowLinks = true;

    protected Node $depthTree;

    protected bool $executeJavaScript = false;

    protected ?Browsershot $browsershot = null;

    protected ?RobotsTxt $robotsTxt = null;

    protected string $crawlRequestFulfilledClass;

    protected string $crawlRequestFailedClass;

    protected int $delayBetweenRequests = 0;

    protected array $allowedMimeTypes = [];

    protected static array $defaultClientOptions = [
        RequestOptions::COOKIES => true,
        RequestOptions::CONNECT_TIMEOUT => 10,
        RequestOptions::TIMEOUT => 10,
        RequestOptions::ALLOW_REDIRECTS => false,
        RequestOptions::HEADERS => [
            'User-Agent' => self::DEFAULT_USER_AGENT,
        ],
    ];

    public static function create(array $clientOptions = []): Crawler
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

        $this->crawlQueue = new ArrayCrawlQueue();

        $this->crawlObservers = new CrawlObserverCollection();

        $this->crawlRequestFulfilledClass = CrawlRequestFulfilled::class;

        $this->crawlRequestFailedClass = CrawlRequestFailed::class;
    }

    public function setConcurrency(int $concurrency): self
    {
        $this->concurrency = $concurrency;

        return $this;
    }

    public function setMaximumResponseSize(int $maximumResponseSizeInBytes): self
    {
        $this->maximumResponseSize = $maximumResponseSizeInBytes;

        return $this;
    }

    public function getMaximumResponseSize(): ?int
    {
        return $this->maximumResponseSize;
    }

    public function setTotalCrawlLimit(int $totalCrawlLimit): self
    {
        $this->totalCrawlLimit = $totalCrawlLimit;

        return $this;
    }

    public function getTotalCrawlLimit(): ?int
    {
        return $this->totalCrawlLimit;
    }

    public function getTotalCrawlCount(): int
    {
        return $this->totalUrlCount;
    }

    public function setCurrentCrawlLimit(int $currentCrawlLimit): self
    {
        $this->currentCrawlLimit = $currentCrawlLimit;

        return $this;
    }

    public function getCurrentCrawlLimit(): ?int
    {
        return $this->currentCrawlLimit;
    }

    public function getCurrentCrawlCount(): int
    {
        return $this->currentUrlCount;
    }

    public function setMaximumDepth(int $maximumDepth): self
    {
        $this->maximumDepth = $maximumDepth;

        return $this;
    }

    public function getMaximumDepth(): ?int
    {
        return $this->maximumDepth;
    }

    public function setDelayBetweenRequests(int $delay): self
    {
        $this->delayBetweenRequests = ($delay * 1000);

        return $this;
    }

    public function getDelayBetweenRequests(): int
    {
        return $this->delayBetweenRequests;
    }

    public function setParseableMimeTypes(array $types): self
    {
        $this->allowedMimeTypes = $types;

        return $this;
    }

    public function getParseableMimeTypes(): array
    {
        return $this->allowedMimeTypes;
    }

    public function ignoreRobots(): self
    {
        $this->respectRobots = false;

        return $this;
    }

    public function respectRobots(): self
    {
        $this->respectRobots = true;

        return $this;
    }

    public function mustRespectRobots(): bool
    {
        return $this->respectRobots;
    }

    public function acceptNofollowLinks(): self
    {
        $this->rejectNofollowLinks = false;

        return $this;
    }

    public function rejectNofollowLinks(): self
    {
        $this->rejectNofollowLinks = true;

        return $this;
    }

    public function mustRejectNofollowLinks(): bool
    {
        return $this->rejectNofollowLinks;
    }

    public function getRobotsTxt(): RobotsTxt
    {
        return $this->robotsTxt;
    }

    public function setCrawlQueue(CrawlQueue $crawlQueue): self
    {
        $this->crawlQueue = $crawlQueue;

        return $this;
    }

    public function getCrawlQueue(): CrawlQueue
    {
        return $this->crawlQueue;
    }

    public function executeJavaScript(): self
    {
        $this->executeJavaScript = true;

        return $this;
    }

    public function doNotExecuteJavaScript(): self
    {
        $this->executeJavaScript = false;

        return $this;
    }

    public function mayExecuteJavascript(): bool
    {
        return $this->executeJavaScript;
    }

    /**
     * @param \Spatie\Crawler\CrawlObservers\CrawlObserver|array[\Spatie\Crawler\CrawlObserver] $crawlObservers
     *
     * @return $this
     */
    public function setCrawlObserver($crawlObservers): self
    {
        if (! is_array($crawlObservers)) {
            $crawlObservers = [$crawlObservers];
        }

        return $this->setCrawlObservers($crawlObservers);
    }

    public function setCrawlObservers(array $crawlObservers): self
    {
        $this->crawlObservers = new CrawlObserverCollection($crawlObservers);

        return $this;
    }

    public function addCrawlObserver(CrawlObserver $crawlObserver): self
    {
        $this->crawlObservers->addObserver($crawlObserver);

        return $this;
    }

    public function getCrawlObservers(): CrawlObserverCollection
    {
        return $this->crawlObservers;
    }

    public function setCrawlProfile(CrawlProfile $crawlProfile): self
    {
        $this->crawlProfile = $crawlProfile;

        return $this;
    }

    public function getCrawlProfile(): CrawlProfile
    {
        return $this->crawlProfile;
    }

    public function setCrawlFulfilledHandlerClass(string $crawlRequestFulfilledClass): self
    {
        $baseClass = CrawlRequestFulfilled::class;

        if (! is_subclass_of($crawlRequestFulfilledClass, $baseClass)) {
            throw InvalidCrawlRequestHandler::doesNotExtendBaseClass($crawlRequestFulfilledClass, $baseClass);
        }

        $this->crawlRequestFulfilledClass = $crawlRequestFulfilledClass;

        return $this;
    }

    public function setCrawlFailedHandlerClass(string $crawlRequestFailedClass): self
    {
        $baseClass = CrawlRequestFailed::class;

        if (! is_subclass_of($crawlRequestFailedClass, $baseClass)) {
            throw InvalidCrawlRequestHandler::doesNotExtendBaseClass($crawlRequestFailedClass, $baseClass);
        }

        $this->crawlRequestFailedClass = $crawlRequestFailedClass;

        return $this;
    }

    public function setBrowsershot(Browsershot $browsershot)
    {
        $this->browsershot = $browsershot;

        return $this;
    }

    public function setUserAgent(string $userAgent): self
    {
        $clientOptions = $this->client->getConfig();

        $headers = array_change_key_case($clientOptions['headers']);
        $headers['user-agent'] = $userAgent;

        $clientOptions['headers'] = $headers;

        $this->client = new Client($clientOptions);

        return $this;
    }

    public function getUserAgent(): string
    {
        $headers = $this->client->getConfig('headers');

        foreach (array_keys($headers) as $name) {
            if (strtolower($name) === 'user-agent') {
                return (string) $headers[$name];
            }
        }

        return static::DEFAULT_USER_AGENT;
    }

    public function getBrowsershot(): Browsershot
    {
        if (! $this->browsershot) {
            $this->browsershot = new Browsershot();
        }

        return $this->browsershot;
    }

    public function getBaseUrl(): UriInterface
    {
        return $this->baseUrl;
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

        $this->totalUrlCount = $this->crawlQueue->getProcessedUrlCount();

        $this->baseUrl = $baseUrl;

        $crawlUrl = CrawlUrl::create($this->baseUrl);

        $this->robotsTxt = $this->createRobotsTxt($crawlUrl->url);

        if ($this->robotsTxt->allows((string) $crawlUrl->url, $this->getUserAgent()) ||
            ! $this->respectRobots
        ) {
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
        if (is_null($this->maximumDepth)) {
            return new Node((string) $url);
        }

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

    protected function startCrawlingQueue(): void
    {
        while (
            $this->reachedCrawlLimits() === false &&
            $this->crawlQueue->hasPendingUrls()
        ) {
            $pool = new Pool($this->client, $this->getCrawlRequests(), [
                'concurrency' => $this->concurrency,
                'options' => $this->client->getConfig(),
                'fulfilled' => new $this->crawlRequestFulfilledClass($this),
                'rejected' => new $this->crawlRequestFailedClass($this),
            ]);

            $promise = $pool->promise();

            $promise->wait();
        }
    }

    protected function createRobotsTxt(UriInterface $uri): RobotsTxt
    {
        return RobotsTxt::create($uri->withPath('/robots.txt'));
    }

    protected function getCrawlRequests(): Generator
    {
        while (
            $this->reachedCrawlLimits() === false &&
            $crawlUrl = $this->crawlQueue->getPendingUrl()
        ) {
            if (
                $this->crawlProfile->shouldCrawl($crawlUrl->url) === false ||
                $this->crawlQueue->hasAlreadyBeenProcessed($crawlUrl)
            ) {
                $this->crawlQueue->markAsProcessed($crawlUrl);

                continue;
            }

            foreach ($this->crawlObservers as $crawlObserver) {
                $crawlObserver->willCrawl($crawlUrl->url);
            }

            $this->totalUrlCount++;
            $this->currentUrlCount++;
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

        $this->crawlQueue->add($crawlUrl);

        return $this;
    }

    public function reachedCrawlLimits(): bool
    {
        $totalCrawlLimit = $this->getTotalCrawlLimit();
        if (! is_null($totalCrawlLimit) && $this->getTotalCrawlCount() >= $totalCrawlLimit) {
            return true;
        }

        $currentCrawlLimit = $this->getCurrentCrawlLimit();
        if (! is_null($currentCrawlLimit) && $this->getCurrentCrawlCount() >= $currentCrawlLimit) {
            return true;
        }

        return false;
    }
}
