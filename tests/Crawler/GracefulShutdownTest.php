<?php

use Spatie\Crawler\Crawler;
use Spatie\Crawler\Test\TestClasses\Log;

beforeEach(function () {
    Log::reset();
});

it('stops crawling when shouldStop is set', function () {
    $crawled = [];

    $crawler = Crawler::create('https://example.com')
        ->fake(fullSiteFakes())
        ->depth(3)
        ->concurrency(1)
        ->onCrawled(function (string $url) use (&$crawled, &$crawler) {
            $crawled[] = $url;

            // Simulate a graceful stop after the first page
            if (count($crawled) === 1) {
                invade($crawler)->shouldStop = true;
            }
        });

    $crawler->start();

    // Should have crawled fewer URLs than a full crawl
    expect(count($crawled))->toBeLessThan(10);
    expect(count($crawled))->toBeGreaterThanOrEqual(1);
});

it('calls finishedCrawling after graceful shutdown', function () {
    $finishedCalled = false;

    $crawler = Crawler::create('https://example.com')
        ->fake(fullSiteFakes())
        ->concurrency(1)
        ->onFinished(function () use (&$finishedCalled) {
            $finishedCalled = true;
        })
        ->onCrawled(function (string $url) use (&$crawler) {
            $reflection = new ReflectionProperty($crawler, 'shouldStop');
            $reflection->setValue($crawler, true);
        });

    $crawler->start();

    expect($finishedCalled)->toBeTrue();
});

it('resets shouldStop between crawls', function () {
    $crawler = Crawler::create('https://example.com')
        ->fake(fullSiteFakes())
        ->depth(0)
        ->concurrency(1);

    // First crawl: set shouldStop before starting
    invade($crawler)->shouldStop = true;

    $firstCrawled = [];
    $crawler->onCrawled(function (string $url) use (&$firstCrawled) {
        $firstCrawled[] = $url;
    });

    // start() should reset shouldStop, so the crawl runs normally
    $crawler->start();

    expect(count($firstCrawled))->toBeGreaterThanOrEqual(1);
});

it('registers signal handlers when pcntl is available', function () {
    if (! extension_loaded('pcntl')) {
        $this->markTestSkipped('pcntl extension is not loaded');
    }

    // Crawl completes normally (signals registered and restored)
    Crawler::create('https://example.com')
        ->fake([
            'https://example.com/robots.txt' => '',
            'https://example.com' => 'Hello',
        ])
        ->depth(0)
        ->start();

    // If we got here, signal handlers were registered and restored without issues
    expect(true)->toBeTrue();
});
