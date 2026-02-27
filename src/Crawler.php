<?php

namespace Spatie\Crawler;

use Exception;
use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Collection;
use Spatie\Crawler\Concerns\ConfiguresRequests;
use Spatie\Crawler\Concerns\HasCrawlLimits;
use Spatie\Crawler\Concerns\HasCrawlObservers;
use Spatie\Crawler\Concerns\HasCrawlQueue;
use Spatie\Crawler\Concerns\HasCrawlScope;
use Spatie\Crawler\CrawlObservers\CollectUrlsObserver;
use Spatie\Crawler\CrawlObservers\CrawlObserverCollection;
use Spatie\Crawler\CrawlQueues\ArrayCrawlQueue;
use Spatie\Crawler\Enums\ResourceType;
use Spatie\Crawler\Exceptions\InvalidCrawlRequestHandler;
use Spatie\Crawler\Exceptions\MissingJavaScriptRenderer;
use Spatie\Crawler\Handlers\CrawlRequestFailed;
use Spatie\Crawler\Handlers\CrawlRequestFulfilled;
use Spatie\Crawler\JavaScriptRenderers\BrowsershotRenderer;
use Spatie\Crawler\JavaScriptRenderers\JavaScriptRenderer;
use Spatie\Crawler\Throttlers\Throttle;
use Spatie\Crawler\UrlParsers\LinkUrlParser;
use Spatie\Crawler\UrlParsers\SitemapUrlParser;
use Spatie\Crawler\UrlParsers\UrlParser;
use Spatie\Robots\RobotsTxt;

class Crawler
{
    use ConfiguresRequests;
    use HasCrawlLimits;
    use HasCrawlObservers;
    use HasCrawlQueue;
    use HasCrawlScope;

    public const DEFAULT_USER_AGENT = '*';

    protected string $baseUrl;

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

    protected ?Throttle $throttle = null;

    protected array $allowedMimeTypes = [];

    protected string $defaultScheme = 'https';

    protected int $concurrency = 10;

    protected ?array $fakes = null;

    protected bool $shouldStop = false;

    /** @var array<int, ResourceType> */
    protected array $extractResourceTypes = [ResourceType::Link];

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

    public function throttle(Throttle $throttle): self
    {
        $this->throttle = $throttle;

        return $this;
    }

    public function maxResponseSizeInBytes(int $maximumResponseSizeInBytes): self
    {
        $this->maximumResponseSize = $maximumResponseSizeInBytes;

        return $this;
    }

    public function allowedMimeTypes(array $types): self
    {
        $this->allowedMimeTypes = $types;

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

    public function rejectNofollowLinks(): self
    {
        $this->rejectNofollowLinks = true;

        return $this;
    }

    // Resource type extraction

    public function alsoExtract(ResourceType ...$types): self
    {
        foreach ($types as $type) {
            if (! in_array($type, $this->extractResourceTypes, true)) {
                $this->extractResourceTypes[] = $type;
            }
        }

        return $this;
    }

    public function extractAll(): self
    {
        $this->extractResourceTypes = ResourceType::cases();

        return $this;
    }

    public function executeJavaScript(?JavaScriptRenderer $renderer = null): self
    {
        if ($renderer === null) {
            if (! class_exists(\Spatie\Browsershot\Browsershot::class)) {
                throw MissingJavaScriptRenderer::browsershotNotInstalled();
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

    public function parseSitemaps(): self
    {
        $this->urlParser = new SitemapUrlParser;

        return $this;
    }

    public function fake(array $fakes): self
    {
        $this->fakes = $fakes;

        return $this;
    }

    public function start(): void
    {
        $this->shouldStop = false;
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

        $this->registerSignalHandlers();

        try {
            $this->startCrawlingQueue($client);
        } finally {
            $this->unregisterSignalHandlers();
        }

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

    public function fulfilledHandler(string $crawlRequestFulfilledClass): self
    {
        $baseClass = CrawlRequestFulfilled::class;

        if (! is_subclass_of($crawlRequestFulfilledClass, $baseClass)) {
            throw InvalidCrawlRequestHandler::doesNotExtendBaseClass($crawlRequestFulfilledClass, $baseClass);
        }

        $this->crawlRequestFulfilledClass = $crawlRequestFulfilledClass;

        return $this;
    }

    public function failedHandler(string $crawlRequestFailedClass): self
    {
        $baseClass = CrawlRequestFailed::class;

        if (! is_subclass_of($crawlRequestFailedClass, $baseClass)) {
            throw InvalidCrawlRequestHandler::doesNotExtendBaseClass($crawlRequestFailedClass, $baseClass);
        }

        $this->crawlRequestFailedClass = $crawlRequestFailedClass;

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

    public function getThrottle(): ?Throttle
    {
        return $this->throttle;
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

    public function getUrlParser(): UrlParser
    {
        if ($this->urlParser !== null) {
            return $this->urlParser;
        }

        return new LinkUrlParser($this->rejectNofollowLinks, $this->extractResourceTypes);
    }

    public function getJavaScriptRenderer(): ?JavaScriptRenderer
    {
        return $this->javaScriptRenderer;
    }

    public function mayExecuteJavascript(): bool
    {
        return $this->javaScriptRenderer !== null;
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

    protected function shouldAddToCrawlQueue(CrawlUrl $crawlUrl): bool
    {
        if ($this->matchesAlwaysCrawl($crawlUrl->url)) {
            return true;
        }

        if ($this->matchesNeverCrawl($crawlUrl->url)) {
            return false;
        }

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
            $this->shouldStop === false &&
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
        } catch (Exception $exception) {
            return new RobotsTxt('');
        }
    }

    protected function getCrawlRequests(): Generator
    {
        while (
            $this->shouldStop === false &&
            $this->reachedCrawlLimits() === false &&
            $this->reachedTimeLimits() === false &&
            $crawlUrl = $this->crawlQueue->getPendingUrl()
        ) {
            if ($this->crawlQueue->hasAlreadyBeenProcessed($crawlUrl)) {
                $this->crawlQueue->markAsProcessed($crawlUrl);

                continue;
            }

            $shouldCrawl = $this->matchesAlwaysCrawl($crawlUrl->url)
                || $this->getCrawlProfile()->shouldCrawl($crawlUrl->url);

            if (! $shouldCrawl) {
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

    protected function registerSignalHandlers(): void
    {
        if (! extension_loaded('pcntl')) {
            return;
        }

        pcntl_async_signals(true);

        pcntl_signal(SIGINT, function () {
            $this->shouldStop = true;
        });

        pcntl_signal(SIGTERM, function () {
            $this->shouldStop = true;
        });
    }

    protected function unregisterSignalHandlers(): void
    {
        if (! extension_loaded('pcntl')) {
            return;
        }

        pcntl_signal(SIGINT, SIG_DFL);
        pcntl_signal(SIGTERM, SIG_DFL);
    }
}
