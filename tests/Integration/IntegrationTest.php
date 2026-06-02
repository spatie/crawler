<?php

use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlResponse;
use Spatie\Crawler\Test\TestServer\TestServer;
use Spatie\Crawler\Throttlers\AdaptiveThrottle;
use Spatie\Crawler\Throttlers\FixedDelayThrottle;
use Spatie\Crawler\Throttlers\Throttle;
use Spatie\Crawler\TransferStatistics;

beforeAll(function () {
    TestServer::start();
});

afterAll(function () {
    TestServer::stop();
});

it('crawls a site and discovers all linked pages', function () {
    $urls = Crawler::create(TestServer::baseUrl())
        ->ignoreRobots()
        ->depth(1)
        ->concurrency(1)
        ->foundUrls();

    $paths = array_map(fn ($crawledUrl) => parse_url($crawledUrl->url, PHP_URL_PATH), $urls);
    sort($paths);

    expect(array_values($paths))->toBe(['/', '/page1', '/page2', '/page3', '/slow']);

    // All pages should return 200.
    expect($urls)->each(fn ($url) => $url->status->toBe(200));

    // Child pages should have the homepage as foundOnUrl.
    $page1 = array_values(array_filter($urls, fn ($crawledUrl) => str_contains($crawledUrl->url, '/page1')))[0] ?? null;
    expect($page1->foundOnUrl)->toBe(TestServer::baseUrl().'/');
    expect($page1->depth)->toBe(1);
});

it('adaptive throttle records real response times', function () {
    $throttle = new AdaptiveThrottle(minDelayMs: 10, maxDelayMs: 5000);

    Crawler::create(TestServer::baseUrl().'/slow')
        ->ignoreRobots()
        ->depth(0)
        ->concurrency(1)
        ->throttle($throttle)
        ->onCrawled(function () {})
        ->start();

    $currentDelay = invade($throttle)->currentDelayMs;

    // The /slow endpoint has a 300ms delay, so the recorded transfer time
    // should push currentDelayMs well above the 10ms minimum.
    expect($currentDelay)->toBeGreaterThan(50);
});

it('fixed delay throttle works with real requests', function () {
    $start = microtime(true);

    Crawler::create(TestServer::baseUrl())
        ->ignoreRobots()
        ->depth(0)
        ->concurrency(1)
        ->throttle(new FixedDelayThrottle(200))
        ->onCrawled(function () {})
        ->start();

    $elapsed = (microtime(true) - $start) * 1000;

    expect($elapsed)->toBeGreaterThan(150);
});

it('does not let delay block concurrent in-flight requests', function () {
    // Resolves every in-flight request together after 300ms, simulating a batch
    // of requests that can complete concurrently without real sockets.
    $makeHandler = fn () => new class
    {
        /** @var array<int, array{Promise, string}> */
        private array $pending = [];

        public function __invoke(RequestInterface $request): Promise
        {
            $promise = new Promise(function () {
                $this->resolvePendingRequests();
            });

            $this->pending[] = [$promise, (string) $request->getUri()];

            return $promise;
        }

        private function resolvePendingRequests(): void
        {
            if ($this->pending === []) {
                return;
            }

            usleep(300_000);

            $pending = $this->pending;
            $this->pending = [];

            foreach ($pending as [$promise, $url]) {
                $promise->resolve(new Response(200, ['Content-Type' => 'text/html'], $this->htmlFor($url)));
            }
        }

        private function htmlFor(string $url): string
        {
            if (parse_url($url, PHP_URL_PATH) !== '/') {
                return '<html><body>leaf</body></html>';
            }

            $body = '<html><body>';

            for ($i = 1; $i <= 20; $i++) {
                $body .= "<a href=\"/p{$i}\">p{$i}</a>";
            }

            return $body.'</body></html>';
        }
    };

    $measureCrawl = function (int $delayInMilliseconds) use ($makeHandler): array {
        $count = 0;
        $start = microtime(true);

        Crawler::create('https://example.com/', ['handler' => $makeHandler()])
            ->ignoreRobots()
            ->concurrency(20)
            ->delay($delayInMilliseconds)
            ->onCrawled(function () use (&$count) {
                $count++;
            })
            ->start();

        return ['count' => $count, 'elapsed' => microtime(true) - $start];
    };

    $withoutDelay = $measureCrawl(0);
    $withDelay = $measureCrawl(100);

    expect($withoutDelay['count'])->toBe(21);
    expect($withDelay['count'])->toBe(21);

    // The 20 leaf requests are all in flight together. A 100ms per-request delay
    // is hidden behind the 300ms batch, so it must not add ~21 * 100ms serially.
    expect($withDelay['elapsed'])->toBeLessThan($withoutDelay['elapsed'] + 0.75);
});

it('url normalization deduplicates across a real crawl', function () {
    $crawled = [];

    Crawler::create(TestServer::baseUrl())
        ->ignoreRobots()
        ->depth(1)
        ->concurrency(1)
        ->onCrawled(function (string $url) use (&$crawled) {
            $crawled[] = $url;
        })
        ->start();

    $page1Crawls = array_filter($crawled, fn (string $url) => str_contains($url, '/page1'));

    expect($page1Crawls)->toHaveCount(1);
});

it('graceful shutdown stops a real crawl', function () {
    $crawled = [];
    $finishedCalled = false;

    $crawler = Crawler::create(TestServer::baseUrl())
        ->ignoreRobots()
        ->depth(3)
        ->concurrency(1)
        ->onCrawled(function (string $url) use (&$crawled, &$crawler) {
            $crawled[] = $url;

            if (count($crawled) === 1) {
                invade($crawler)->shouldStop = true;
            }
        })
        ->onFinished(function () use (&$finishedCalled) {
            $finishedCalled = true;
        });

    $crawler->start();

    // A full crawl at depth 3 would visit many pages; stopping after the first
    // should result in significantly fewer.
    expect(count($crawled))->toBeLessThan(5);
    expect(count($crawled))->toBeGreaterThanOrEqual(1);
    expect($finishedCalled)->toBeTrue();
});

it('throttle is called on real failed requests', function () {
    $sleepCount = 0;

    $throttle = new class($sleepCount) implements Throttle
    {
        public function __construct(protected int &$sleepCount) {}

        public function sleep(): void
        {
            $this->sleepCount++;
        }

        public function recordResponseTime(float $seconds): void {}
    };

    Crawler::create(TestServer::baseUrl().'/link-to-404')
        ->ignoreRobots()
        ->depth(1)
        ->concurrency(1)
        ->throttle($throttle)
        ->onCrawled(function () {})
        ->onFailed(function () {})
        ->start();

    // sleep() should be called for both the parent page (fulfilled) and the 404 (failed).
    expect($sleepCount)->toBeGreaterThanOrEqual(2);
});

it('respects robots.txt over real http', function () {
    $crawled = [];

    Crawler::create(TestServer::baseUrl().'/link-to-secret')
        ->respectRobots()
        ->depth(1)
        ->concurrency(1)
        ->onCrawled(function (string $url) use (&$crawled) {
            $crawled[] = $url;
        })
        ->start();

    $secretCrawls = array_filter($crawled, fn (string $url) => str_contains($url, '/secret'));

    expect($secretCrawls)->toBeEmpty();
});

it('respects depth limits with real http', function () {
    $crawled = [];

    Crawler::create(TestServer::baseUrl().'/deep/1')
        ->ignoreRobots()
        ->depth(2)
        ->concurrency(1)
        ->onCrawled(function (string $url) use (&$crawled) {
            $crawled[] = $url;
        })
        ->start();

    // depth(2) from /deep/1 should reach /deep/1, /deep/2, /deep/3 but not /deep/4.
    expect($crawled)->toHaveCount(3);
    expect($crawled)->each->toMatch('/\/deep\/[123]$/');
});

it('exposes transfer stats on crawl response', function () {
    $stats = null;

    Crawler::create(TestServer::baseUrl())
        ->ignoreRobots()
        ->depth(0)
        ->concurrency(1)
        ->onCrawled(function (string $url, CrawlResponse $response) use (&$stats) {
            $stats = $response->transferStats();
        })
        ->start();

    expect($stats)->toBeInstanceOf(TransferStatistics::class);
    expect($stats->transferTimeInMs())->toBeGreaterThan(0);
});

it('applies custom guzzle middleware to requests', function () {
    $requestedUrls = [];

    $trackRequests = Middleware::mapRequest(function ($request) use (&$requestedUrls) {
        $requestedUrls[] = (string) $request->getUri();

        return $request->withHeader('X-Custom-Middleware', 'applied');
    });

    Crawler::create(TestServer::baseUrl())
        ->ignoreRobots()
        ->depth(0)
        ->concurrency(1)
        ->middleware($trackRequests, 'track-requests')
        ->onCrawled(function () {})
        ->start();

    expect($requestedUrls)->toHaveCount(1);
    expect($requestedUrls[0])->toContain(TestServer::baseUrl());
});
