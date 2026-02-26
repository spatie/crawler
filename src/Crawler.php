<?php

namespace Spatie\Crawler;

use Closure;
use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;
use Spatie\Crawler\CrawlObservers\CollectUrlsObserver;
use Spatie\Crawler\CrawlObservers\CrawlObserver;
use Spatie\Crawler\CrawlObservers\CrawlObserverCollection;
use Spatie\Crawler\CrawlProfiles\ClosureCrawlProfile;
use Spatie\Crawler\CrawlProfiles\CrawlAllUrls;
use Spatie\Crawler\CrawlProfiles\CrawlInternalUrls;
use Spatie\Crawler\CrawlProfiles\CrawlProfile;
use Spatie\Crawler\CrawlProfiles\CrawlSubdomains;
use Spatie\Crawler\CrawlQueues\ArrayCrawlQueue;
use Spatie\Crawler\CrawlQueues\CrawlQueue;
use Spatie\Crawler\Exceptions\InvalidCrawlRequestHandler;
use Spatie\Crawler\Faking\FakeHandler;
use Spatie\Crawler\Handlers\CrawlRequestFailed;
use Spatie\Crawler\Handlers\CrawlRequestFulfilled;
use Spatie\Crawler\JavaScriptRenderers\BrowsershotRenderer;
use Spatie\Crawler\JavaScriptRenderers\JavaScriptRenderer;
use Spatie\Crawler\UrlParsers\LinkUrlParser;
use Spatie\Crawler\UrlParsers\SitemapUrlParser;
use Spatie\Crawler\UrlParsers\UrlParser;
use Spatie\Robots\RobotsTxt;

class Crawler
{
    public const DEFAULT_USER_AGENT = '*';

    protected string $baseUrl;

    protected CrawlObserverCollection $crawlObservers;

    protected ?CrawlProfile $crawlProfile = null;

    protected CrawlQueue $crawlQueue;

    protected int $totalUrlCount = 0;

    protected int $currentUrlCount = 0;

    protected ?int $totalCrawlLimit = null;

    protected ?int $currentCrawlLimit = null;

    protected ?int $startedAt = null;

    protected int $executionTime = 0;

    protected ?int $totalExecutionTimeLimit = null;

    protected ?int $currentExecutionTimeLimit = null;

    protected int $maximumResponseSize = 1024 * 1024 * 2;

    protected ?int $maximumDepth = null;

    protected bool $respectRobots = true;

    protected bool $rejectNofollowLinks = true;

    protected ?JavaScriptRenderer $javaScriptRenderer = null;

    protected ?RobotsTxt $robotsTxt = null;

    protected string $crawlRequestFulfilledClass;

    protected string $crawlRequestFailedClass;

    protected ?UrlParser $urlParser = null;

    protected int $delayBetweenRequests = 0;

    protected array $allowedMimeTypes = [];

    protected string $defaultScheme = 'https';

    protected int $concurrency = 10;

    protected ?string $userAgent = null;

    protected ?string $scopeMode = null;

    protected ?array $fakes = null;

    protected array $clientOptions;

    protected ?Client $client = null;

    protected static array $defaultClientOptions = [
        RequestOptions::COOKIES => true,
        RequestOptions::CONNECT_TIMEOUT => 10,
        RequestOptions::TIMEOUT => 10,
        RequestOptions::ALLOW_REDIRECTS => false,
        RequestOptions::HEADERS => [
            'User-Agent' => self::DEFAULT_USER_AGENT,
        ],
    ];

    public static function create(string $url = '', array $clientOptions = []): static
    {
        $crawler = new static($clientOptions);

        if ($url !== '') {
            $crawler->baseUrl = $url;
        }

        return $crawler;
    }

    public function __construct(array $clientOptions = [])
    {
        $this->clientOptions = count($clientOptions)
            ? $clientOptions
            : static::$defaultClientOptions;

        $this->crawlQueue = new ArrayCrawlQueue;
        $this->crawlObservers = new CrawlObserverCollection;
        $this->crawlRequestFulfilledClass = CrawlRequestFulfilled::class;
        $this->crawlRequestFailedClass = CrawlRequestFailed::class;
    }

    // Fluent configuration methods

    public function depth(int $maximumDepth): self
    {
        $this->maximumDepth = $maximumDepth;

        return $this;
    }

    public function concurrency(int $concurrency): self
    {
        $this->concurrency = $concurrency;

        return $this;
    }

    public function delay(int $delayInMilliseconds): self
    {
        $this->delayBetweenRequests = ($delayInMilliseconds * 1000);

        return $this;
    }

    public function userAgent(string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function limit(int $totalCrawlLimit): self
    {
        $this->totalCrawlLimit = $totalCrawlLimit;

        return $this;
    }

    public function limitPerExecution(int $currentCrawlLimit): self
    {
        $this->currentCrawlLimit = $currentCrawlLimit;

        return $this;
    }

    public function maxResponseSize(int $maximumResponseSizeInBytes): self
    {
        $this->maximumResponseSize = $maximumResponseSizeInBytes;

        return $this;
    }

    public function allowedMimeTypes(array $types): self
    {
        $this->allowedMimeTypes = $types;

        return $this;
    }

    public function timeLimit(int $totalExecutionTimeLimitInSeconds): self
    {
        $this->totalExecutionTimeLimit = $totalExecutionTimeLimitInSeconds;

        return $this;
    }

    public function timeLimitPerExecution(int $currentExecutionTimeLimitInSeconds): self
    {
        $this->currentExecutionTimeLimit = $currentExecutionTimeLimitInSeconds;

        return $this;
    }

    public function defaultScheme(string $defaultScheme): self
    {
        $this->defaultScheme = $defaultScheme;

        return $this;
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

    public function followNofollow(): self
    {
        $this->rejectNofollowLinks = false;

        return $this;
    }

    // Scope methods

    public function internalOnly(): self
    {
        $this->scopeMode = 'internal';
        $this->crawlProfile = null;

        return $this;
    }

    public function includeSubdomains(): self
    {
        $this->scopeMode = 'subdomains';
        $this->crawlProfile = null;

        return $this;
    }

    public function shouldCrawl(Closure $closure): self
    {
        $this->crawlProfile = new ClosureCrawlProfile($closure);
        $this->scopeMode = null;

        return $this;
    }

    public function setCrawlProfile(CrawlProfile $crawlProfile): self
    {
        $this->crawlProfile = $crawlProfile;
        $this->scopeMode = null;

        return $this;
    }

    // Callback methods

    public function onCrawled(Closure $callback): self
    {
        $this->crawlObservers->onCrawled($callback);

        return $this;
    }

    public function onFailed(Closure $callback): self
    {
        $this->crawlObservers->onFailed($callback);

        return $this;
    }

    public function onFinished(Closure $callback): self
    {
        $this->crawlObservers->onFinished($callback);

        return $this;
    }

    // Observer support

    public function addObserver(CrawlObserver $observer): self
    {
        $this->crawlObservers->addObserver($observer);

        return $this;
    }

    // JS rendering

    public function executeJavaScript(?JavaScriptRenderer $renderer = null): self
    {
        if ($renderer === null) {
            if (! class_exists(\Spatie\Browsershot\Browsershot::class)) {
                throw new \RuntimeException(
                    'To execute JavaScript, install spatie/browsershot or provide a custom JavaScriptRenderer.'
                );
            }

            $renderer = new BrowsershotRenderer;
        }

        $this->javaScriptRenderer = $renderer;

        return $this;
    }

    public function doNotExecuteJavaScript(): self
    {
        $this->javaScriptRenderer = null;

        return $this;
    }

    // Sitemap parsing

    public function parseSitemaps(): self
    {
        $this->urlParser = new SitemapUrlParser;

        return $this;
    }

    // Faking

    public function fake(array $fakes): self
    {
        $this->fakes = $fakes;

        return $this;
    }

    // Main execution

    public function start(): void
    {
        $this->startedAt = time();

        $baseUrl = $this->normalizeBaseUrl($this->baseUrl);
        $this->baseUrl = $baseUrl;

        $this->totalUrlCount = $this->crawlQueue->getProcessedUrlCount();

        $client = $this->buildClient();

        $this->resolveScope();

        if ($this->respectRobots) {
            $this->robotsTxt = $this->createRobotsTxt($client);
        }

        $crawlUrl = CrawlUrl::create($this->baseUrl);

        if ($this->shouldAddToCrawlQueue($crawlUrl)) {
            $this->addToCrawlQueue($crawlUrl);
        }

        $this->startCrawlingQueue($client);

        $this->crawlObservers->finishedCrawling();

        $this->executionTime += time() - $this->startedAt;
        $this->startedAt = null;
    }

    /** @return Collection<CrawledUrl> */
    public function collectUrls(): Collection
    {
        $collector = new CollectUrlsObserver;

        $this->crawlObservers->addObserver($collector);

        $this->start();

        return $collector->getUrls();
    }

    // Legacy support

    public function startCrawling(string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;

        $this->start();
    }

    // Backward compatible setter methods

    public function setMaximumDepth(int $maximumDepth): self
    {
        return $this->depth($maximumDepth);
    }

    public function setConcurrency(int $concurrency): self
    {
        return $this->concurrency($concurrency);
    }

    public function setMaximumResponseSize(int $maximumResponseSizeInBytes): self
    {
        return $this->maxResponseSize($maximumResponseSizeInBytes);
    }

    public function setTotalCrawlLimit(int $totalCrawlLimit): self
    {
        return $this->limit($totalCrawlLimit);
    }

    public function setCurrentCrawlLimit(int $currentCrawlLimit): self
    {
        return $this->limitPerExecution($currentCrawlLimit);
    }

    public function setTotalExecutionTimeLimit(int $totalExecutionTimeLimitInSeconds): self
    {
        return $this->timeLimit($totalExecutionTimeLimitInSeconds);
    }

    public function setCurrentExecutionTimeLimit(int $currentExecutionTimeLimitInSeconds): self
    {
        return $this->timeLimitPerExecution($currentExecutionTimeLimitInSeconds);
    }

    public function setDelayBetweenRequests(int $delayInMilliseconds): self
    {
        return $this->delay($delayInMilliseconds);
    }

    public function setParseableMimeTypes(array $types): self
    {
        return $this->allowedMimeTypes($types);
    }

    public function setDefaultScheme(string $defaultScheme): self
    {
        return $this->defaultScheme($defaultScheme);
    }

    public function setUserAgent(string $userAgent): self
    {
        return $this->userAgent($userAgent);
    }

    public function setCrawlObserver(CrawlObserver|array $crawlObservers): self
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
        return $this->addObserver($crawlObserver);
    }

    public function setCrawlQueue(CrawlQueue $crawlQueue): self
    {
        $this->crawlQueue = $crawlQueue;

        return $this;
    }

    public function setUrlParserClass(string $urlParserClass): self
    {
        if ($urlParserClass === SitemapUrlParser::class) {
            $this->urlParser = new SitemapUrlParser;
        } else {
            $this->urlParser = null;
        }

        return $this;
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

    public function acceptNofollowLinks(): self
    {
        return $this->followNofollow();
    }

    public function rejectNofollowLinks(): self
    {
        $this->rejectNofollowLinks = true;

        return $this;
    }

    // Getters

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getMaximumDepth(): ?int
    {
        return $this->maximumDepth;
    }

    public function getMaximumResponseSize(): ?int
    {
        return $this->maximumResponseSize;
    }

    public function getDelayBetweenRequests(): int
    {
        return $this->delayBetweenRequests;
    }

    public function getAllowedMimeTypes(): array
    {
        return $this->allowedMimeTypes;
    }

    public function getDefaultScheme(): string
    {
        return $this->defaultScheme;
    }

    public function mustRespectRobots(): bool
    {
        return $this->respectRobots;
    }

    public function mustRejectNofollowLinks(): bool
    {
        return $this->rejectNofollowLinks;
    }

    public function getRobotsTxt(): ?RobotsTxt
    {
        return $this->robotsTxt;
    }

    public function getCrawlQueue(): CrawlQueue
    {
        return $this->crawlQueue;
    }

    public function getCrawlObservers(): CrawlObserverCollection
    {
        return $this->crawlObservers;
    }

    public function getCrawlProfile(): CrawlProfile
    {
        return $this->crawlProfile ?? new CrawlAllUrls;
    }

    public function getUrlParser(): UrlParser
    {
        if ($this->urlParser !== null) {
            return $this->urlParser;
        }

        return new LinkUrlParser($this->rejectNofollowLinks);
    }

    public function getJavaScriptRenderer(): ?JavaScriptRenderer
    {
        return $this->javaScriptRenderer;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent ?? static::DEFAULT_USER_AGENT;
    }

    public function getTotalCrawlLimit(): ?int
    {
        return $this->totalCrawlLimit;
    }

    public function getTotalCrawlCount(): int
    {
        return $this->totalUrlCount;
    }

    public function getCurrentCrawlLimit(): ?int
    {
        return $this->currentCrawlLimit;
    }

    public function getCurrentCrawlCount(): int
    {
        return $this->currentUrlCount;
    }

    public function getTotalExecutionTimeLimit(): ?int
    {
        return $this->totalExecutionTimeLimit;
    }

    public function getTotalExecutionTime(): int
    {
        return $this->executionTime + $this->getCurrentExecutionTime();
    }

    public function getCurrentExecutionTimeLimit(): ?int
    {
        return $this->currentExecutionTimeLimit;
    }

    public function getCurrentExecutionTime(): int
    {
        if (is_null($this->startedAt)) {
            return 0;
        }

        return time() - $this->startedAt;
    }

    public function mayExecuteJavascript(): bool
    {
        return $this->javaScriptRenderer !== null;
    }

    // Queue management

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

    public function reachedTimeLimits(): bool
    {
        $totalExecutionTimeLimit = $this->getTotalExecutionTimeLimit();
        if (! is_null($totalExecutionTimeLimit) && $this->getTotalExecutionTime() >= $totalExecutionTimeLimit) {
            return true;
        }

        $currentExecutionTimeLimit = $this->getCurrentExecutionTimeLimit();
        if (! is_null($currentExecutionTimeLimit) && $this->getCurrentExecutionTime() >= $currentExecutionTimeLimit) {
            return true;
        }

        return false;
    }

    // Internal methods

    protected function normalizeBaseUrl(string $baseUrl): string
    {
        $parsed = parse_url($baseUrl);

        if (! isset($parsed['scheme'])) {
            $baseUrl = $this->defaultScheme.'://'.$baseUrl;
        }

        $parsed = parse_url($baseUrl);

        if (! isset($parsed['path']) || $parsed['path'] === '') {
            $baseUrl .= '/';
        }

        return $baseUrl;
    }

    protected function buildClient(): Client
    {
        $options = $this->clientOptions;

        if ($this->userAgent !== null) {
            $options[RequestOptions::HEADERS] = $options[RequestOptions::HEADERS] ?? [];
            $options[RequestOptions::HEADERS]['User-Agent'] = $this->userAgent;

            // Handle case-insensitive headers
            foreach ($options['headers'] ?? [] as $key => $value) {
                if (strtolower($key) === 'user-agent' && $key !== 'User-Agent') {
                    unset($options['headers'][$key]);
                }
            }
        }

        if ($this->fakes !== null) {
            $handler = new FakeHandler($this->fakes);
            $stack = HandlerStack::create($handler);
            $options['handler'] = $stack;
            $options[RequestOptions::HTTP_ERRORS] = false;
        }

        $this->client = new Client($options);

        return $this->client;
    }

    protected function resolveScope(): void
    {
        if ($this->crawlProfile !== null) {
            return;
        }

        $this->crawlProfile = match ($this->scopeMode) {
            'internal' => new CrawlInternalUrls($this->baseUrl),
            'subdomains' => new CrawlSubdomains($this->baseUrl),
            default => new CrawlAllUrls,
        };
    }

    protected function shouldAddToCrawlQueue(CrawlUrl $crawlUrl): bool
    {
        if (! $this->respectRobots) {
            return true;
        }

        if ($this->robotsTxt === null) {
            return false;
        }

        if ($this->robotsTxt->allows($crawlUrl->url, $this->getUserAgent())) {
            return true;
        }

        return false;
    }

    protected function startCrawlingQueue(Client $client): void
    {
        while (
            $this->reachedCrawlLimits() === false &&
            $this->reachedTimeLimits() === false &&
            $this->crawlQueue->hasPendingUrls()
        ) {
            $pool = new Pool($client, $this->getCrawlRequests(), [
                'concurrency' => $this->concurrency,
                'options' => $client->getConfig(),
                'fulfilled' => new $this->crawlRequestFulfilledClass($this),
                'rejected' => new $this->crawlRequestFailedClass($this),
            ]);

            $promise = $pool->promise();

            $promise->wait();
        }
    }

    protected function createRobotsTxt(Client $client): RobotsTxt
    {
        try {
            $parsed = parse_url($this->baseUrl);
            $robotsUrl = ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? '').(isset($parsed['port']) ? ':'.$parsed['port'] : '').'/robots.txt';
            $response = $client->get($robotsUrl);
            $content = (string) $response->getBody();

            return new RobotsTxt($content);
        } catch (\Exception $exception) {
            return new RobotsTxt('');
        }
    }

    protected function getCrawlRequests(): Generator
    {
        while (
            $this->reachedCrawlLimits() === false &&
            $this->reachedTimeLimits() === false &&
            $crawlUrl = $this->crawlQueue->getPendingUrl()
        ) {
            if (
                $this->getCrawlProfile()->shouldCrawl($crawlUrl->url) === false ||
                $this->crawlQueue->hasAlreadyBeenProcessed($crawlUrl)
            ) {
                $this->crawlQueue->markAsProcessed($crawlUrl);

                continue;
            }

            $this->crawlObservers->willCrawl($crawlUrl);

            $this->totalUrlCount++;
            $this->currentUrlCount++;
            $this->crawlQueue->markAsProcessed($crawlUrl);

            yield $crawlUrl->id => new Request('GET', $crawlUrl->url);
        }
    }
}
