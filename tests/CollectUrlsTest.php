<?php

use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawledUrl;

it('can collect urls', function () {
    $urls = Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body><a href="/about">About</a><a href="/contact">Contact</a></body></html>',
            'https://example.com/about' => '<html><body>About</body></html>',
            'https://example.com/contact' => '<html><body>Contact</body></html>',
        ])
        ->ignoreRobots()
        ->collectUrls();

    expect($urls)->toHaveCount(3);
    expect($urls->first())->toBeInstanceOf(CrawledUrl::class);
});

it('collected urls contain status codes', function () {
    $urls = Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body><a href="/missing">Missing</a></body></html>',
        ])
        ->ignoreRobots()
        ->collectUrls();

    $home = $urls->firstWhere('url', 'https://example.com/');
    expect($home)->not->toBeNull();
    expect($home->status)->toBe(200);
});

it('collected urls contain found on url', function () {
    $urls = Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body><a href="/about">About</a></body></html>',
            'https://example.com/about' => '<html><body>About</body></html>',
        ])
        ->ignoreRobots()
        ->collectUrls();

    $about = $urls->firstWhere('url', 'https://example.com/about');
    expect($about)->not->toBeNull();
    expect($about->foundOnUrl)->toBe('https://example.com/');
});

it('collected urls contain depth', function () {
    $urls = Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body><a href="/level1">L1</a></body></html>',
            'https://example.com/level1' => '<html><body><a href="/level2">L2</a></body></html>',
            'https://example.com/level2' => '<html><body>Deep</body></html>',
        ])
        ->ignoreRobots()
        ->collectUrls();

    $home = $urls->firstWhere('url', 'https://example.com/');
    expect($home->depth)->toBe(0);

    $level1 = $urls->firstWhere('url', 'https://example.com/level1');
    expect($level1->depth)->toBe(1);

    $level2 = $urls->firstWhere('url', 'https://example.com/level2');
    expect($level2->depth)->toBe(2);
});

it('collectUrls is additive with observers', function () {
    $crawledFromClosure = [];

    $urls = Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body>Hello</body></html>',
        ])
        ->ignoreRobots()
        ->onCrawled(function (string $url) use (&$crawledFromClosure) {
            $crawledFromClosure[] = $url;
        })
        ->collectUrls();

    expect($urls)->toHaveCount(1);
    expect($crawledFromClosure)->toContain('https://example.com/');
});

it('can collect urls with internalOnly', function () {
    $urls = Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body><a href="/page">Page</a><a href="https://other.com">Other</a></body></html>',
            'https://example.com/page' => '<html><body>Page</body></html>',
            'https://other.com' => '<html><body>Other</body></html>',
        ])
        ->ignoreRobots()
        ->internalOnly()
        ->collectUrls();

    $collectedUrls = $urls->pluck('url')->toArray();
    expect($collectedUrls)->toContain('https://example.com/');
    expect($collectedUrls)->toContain('https://example.com/page');
    expect($collectedUrls)->not->toContain('https://other.com');
});
