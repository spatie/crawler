<?php

use Spatie\Crawler\Crawler;
use Spatie\Crawler\Test\TestServer\TestServer;
use Spatie\Crawler\Throttlers\AdaptiveThrottle;
use Spatie\Crawler\Throttlers\FixedDelayThrottle;
use Spatie\Crawler\Throttlers\Throttle;

beforeAll(function () {
    TestServer::start();
});

afterAll(function () {
    TestServer::stop();
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

    $ref = new ReflectionProperty($throttle, 'currentDelayMs');
    $currentDelay = $ref->getValue($throttle);

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
                $reflection = new ReflectionProperty($crawler, 'shouldStop');
                $reflection->setValue($crawler, true);
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
