<?php

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
        ->collectUrls();

    $paths = $urls->map(fn ($crawledUrl) => parse_url($crawledUrl->url, PHP_URL_PATH))->sort()->values();

    expect($paths->all())->toBe(['/', '/page1', '/page2', '/page3', '/slow']);

    // All pages should return 200.
    expect($urls->every(fn ($crawledUrl) => $crawledUrl->status === 200))->toBeTrue();

    // Child pages should have the homepage as foundOnUrl.
    $page1 = $urls->first(fn ($crawledUrl) => str_contains($crawledUrl->url, '/page1'));
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
