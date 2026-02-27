<?php

use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlProfiles\CrawlInternalUrls;
use Spatie\Crawler\Test\TestClasses\Log;
use Spatie\Crawler\Throttlers\AdaptiveThrottle;
use Spatie\Crawler\Throttlers\FixedDelayThrottle;

beforeEach(function () {
    Log::reset();
});

it('can set a throttle on the crawler', function () {
    $throttle = new FixedDelayThrottle(100);

    $crawler = Crawler::create('https://example.com')
        ->throttle($throttle);

    expect($crawler->getThrottle())->toBe($throttle);
});

it('returns null when no throttle is set', function () {
    $crawler = Crawler::create('https://example.com');

    expect($crawler->getThrottle())->toBeNull();
});

it('applies fixed delay throttle during crawl', function () {
    $start = microtime(true);

    createCrawler()
        ->fake(fullSiteFakes())
        ->depth(0)
        ->throttle(new FixedDelayThrottle(200))
        ->start();

    $elapsed = (microtime(true) - $start) * 1000;

    expect($elapsed)->toBeGreaterThan(150);
});

it('fixed delay throttle ignores response time', function () {
    $throttle = new FixedDelayThrottle(100);

    $throttle->recordResponseTime(5.0);
    $throttle->recordResponseTime(0.001);

    // Should still sleep 100ms regardless of recorded times.
    $start = microtime(true);
    $throttle->sleep();
    $elapsed = (microtime(true) - $start) * 1000;

    expect($elapsed)->toBeGreaterThan(80);
    expect($elapsed)->toBeLessThan(200);
});

it('adaptive throttle adjusts delay based on response time', function () {
    $throttle = new AdaptiveThrottle(minDelayMs: 50, maxDelayMs: 5000);

    // Record a fast response
    $throttle->recordResponseTime(0.05); // 50ms

    $start = microtime(true);
    $throttle->sleep();
    $elapsed = (microtime(true) - $start) * 1000;

    // Delay should be (50 + 50) / 2 = 50ms (clamped to min 50)
    expect($elapsed)->toBeGreaterThan(30);
    expect($elapsed)->toBeLessThan(200);
});

it('adaptive throttle clamps delay to max', function () {
    $throttle = new AdaptiveThrottle(minDelayMs: 50, maxDelayMs: 100);

    // Record a very slow response (20 seconds)
    $throttle->recordResponseTime(20.0);

    $start = microtime(true);
    $throttle->sleep();
    $elapsed = (microtime(true) - $start) * 1000;

    // Should be clamped to maxDelayMs (100ms)
    expect($elapsed)->toBeGreaterThan(80);
    expect($elapsed)->toBeLessThan(200);
});

it('adaptive throttle clamps delay to min', function () {
    $throttle = new AdaptiveThrottle(minDelayMs: 50, maxDelayMs: 5000);

    // Record a very fast response
    $throttle->recordResponseTime(0.001); // 1ms

    $start = microtime(true);
    $throttle->sleep();
    $elapsed = (microtime(true) - $start) * 1000;

    // Should be clamped to minDelayMs (50ms)
    expect($elapsed)->toBeGreaterThan(30);
    expect($elapsed)->toBeLessThan(150);
});

it('adaptive throttle converges over multiple recordings', function () {
    $throttle = new AdaptiveThrottle(minDelayMs: 10, maxDelayMs: 5000);

    // Record a series of 500ms responses
    for ($i = 0; $i < 10; $i++) {
        $throttle->recordResponseTime(0.5); // 500ms
    }

    // After many 500ms recordings starting from min (10ms), delay should converge toward 500ms
    $start = microtime(true);
    $throttle->sleep();
    $elapsed = (microtime(true) - $start) * 1000;

    expect($elapsed)->toBeGreaterThan(300);
});

it('applies throttle on failed requests', function () {
    $sleepCount = 0;

    $throttle = new class($sleepCount) implements \Spatie\Crawler\Throttlers\Throttle
    {
        public function __construct(protected int &$sleepCount) {}

        public function sleep(): void
        {
            $this->sleepCount++;
        }

        public function recordResponseTime(float $seconds): void {}
    };

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com/robots.txt' => '',
            'https://example.com' => '<a href="/missing">link</a>',
            'https://example.com/missing' => \Spatie\Crawler\CrawlResponse::fake('', 404),
        ])
        ->throttle($throttle)
        ->depth(1)
        ->onFailed(function () {})
        ->start();

    // Throttle should have been called for both fulfilled and failed responses
    expect($sleepCount)->toBeGreaterThanOrEqual(2);
});

it('throttle takes precedence over delay', function () {
    $start = microtime(true);

    createCrawler()
        ->fake([
            'https://example.com/robots.txt' => '',
            'https://example.com' => 'Hello',
        ])
        ->depth(0)
        ->delay(2000) // 2 seconds (should be ignored)
        ->throttle(new FixedDelayThrottle(50)) // 50ms
        ->start();

    $elapsed = (microtime(true) - $start) * 1000;

    // Should take roughly 50ms, not 2000ms
    expect($elapsed)->toBeLessThan(1000);
});
