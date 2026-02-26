<?php

use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlResponse;

it('can fake crawling with html strings', function () {
    $crawled = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body><a href="/about">About</a></body></html>',
            'https://example.com/about' => '<html><body>About page</body></html>',
        ])
        ->ignoreRobots()
        ->onCrawled(function (string $url, CrawlResponse $response) use (&$crawled) {
            $crawled[$url] = $response->status();
        })
        ->start();

    expect($crawled)->toHaveKey('https://example.com/');
    expect($crawled)->toHaveKey('https://example.com/about');
    expect($crawled['https://example.com/'])->toBe(200);
    expect($crawled['https://example.com/about'])->toBe(200);
});

it('handles relative urls in fake html', function () {
    $crawled = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body><a href="/page1">Page 1</a><a href="/page2">Page 2</a></body></html>',
            'https://example.com/page1' => '<html><body>Page 1</body></html>',
            'https://example.com/page2' => '<html><body>Page 2</body></html>',
        ])
        ->ignoreRobots()
        ->onCrawled(function (string $url, CrawlResponse $response) use (&$crawled) {
            $crawled[] = $url;
        })
        ->start();

    expect($crawled)->toContain('https://example.com/');
    expect($crawled)->toContain('https://example.com/page1');
    expect($crawled)->toContain('https://example.com/page2');
});

it('respects depth limits with fakes', function () {
    $crawled = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body><a href="/level1">L1</a></body></html>',
            'https://example.com/level1' => '<html><body><a href="/level2">L2</a></body></html>',
            'https://example.com/level2' => '<html><body><a href="/level3">L3</a></body></html>',
            'https://example.com/level3' => '<html><body>Deep page</body></html>',
        ])
        ->ignoreRobots()
        ->depth(2)
        ->onCrawled(function (string $url, CrawlResponse $response) use (&$crawled) {
            $crawled[] = $url;
        })
        ->start();

    expect($crawled)->toContain('https://example.com/');
    expect($crawled)->toContain('https://example.com/level1');
    expect($crawled)->toContain('https://example.com/level2');
    expect($crawled)->not->toContain('https://example.com/level3');
});

it('returns 404 for unfaked urls', function () {
    $crawled = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body><a href="/missing">Missing</a></body></html>',
        ])
        ->ignoreRobots()
        ->onCrawled(function (string $url, CrawlResponse $response) use (&$crawled) {
            $crawled[$url] = $response->status();
        })
        ->start();

    expect($crawled)->toHaveKey('https://example.com/missing');
    expect($crawled['https://example.com/missing'])->toBe(404);
});

it('handles robots.txt in fakes', function () {
    $crawled = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com/robots.txt' => "User-agent: *\nDisallow: /blocked",
            'https://example.com' => '<html><body><a href="/allowed">OK</a><a href="/blocked">No</a></body></html>',
            'https://example.com/allowed' => '<html><body>Allowed</body></html>',
            'https://example.com/blocked' => '<html><body>Blocked</body></html>',
        ])
        ->respectRobots()
        ->onCrawled(function (string $url, CrawlResponse $response) use (&$crawled) {
            $crawled[] = $url;
        })
        ->start();

    expect($crawled)->toContain('https://example.com/');
    expect($crawled)->toContain('https://example.com/allowed');
    expect($crawled)->not->toContain('https://example.com/blocked');
});

it('allows all urls when robots.txt is not faked', function () {
    $crawled = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body><a href="/page">Page</a></body></html>',
            'https://example.com/page' => '<html><body>Page</body></html>',
        ])
        ->respectRobots()
        ->onCrawled(function (string $url, CrawlResponse $response) use (&$crawled) {
            $crawled[] = $url;
        })
        ->start();

    expect($crawled)->toContain('https://example.com/');
    expect($crawled)->toContain('https://example.com/page');
});

it('can collect urls using fake', function () {
    $urls = Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body><a href="/about">About</a></body></html>',
            'https://example.com/about' => '<html><body>About</body></html>',
        ])
        ->ignoreRobots()
        ->collectUrls();

    expect($urls)->toHaveCount(2);
    expect($urls->pluck('url')->toArray())->toContain('https://example.com/');
    expect($urls->pluck('url')->toArray())->toContain('https://example.com/about');
});

it('can use internalOnly with fake', function () {
    $crawled = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body><a href="/page">Page</a><a href="https://other.com">Other</a></body></html>',
            'https://example.com/page' => '<html><body>Page</body></html>',
            'https://other.com' => '<html><body>Other</body></html>',
        ])
        ->ignoreRobots()
        ->internalOnly()
        ->onCrawled(function (string $url, CrawlResponse $response) use (&$crawled) {
            $crawled[] = $url;
        })
        ->start();

    expect($crawled)->toContain('https://example.com/');
    expect($crawled)->toContain('https://example.com/page');
    expect($crawled)->not->toContain('https://other.com');
});
