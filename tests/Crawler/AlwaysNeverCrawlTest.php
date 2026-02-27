<?php

use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlProfiles\CrawlInternalUrls;

it('always crawls urls matching alwaysCrawl patterns', function () {
    $crawled = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body><a href="https://cdn.example.com/file.js">CDN</a><a href="https://other.com/page">Other</a></body></html>',
            'https://cdn.example.com/file.js' => 'JS content',
            'https://other.com/page' => 'Other page',
        ])
        ->ignoreRobots()
        ->internalOnly()
        ->alwaysCrawl(['https://cdn.example.com/*'])
        ->onCrawled(function (string $url) use (&$crawled) {
            $crawled[] = $url;
        })
        ->start();

    expect($crawled)->toContain('https://example.com/');
    expect($crawled)->toContain('https://cdn.example.com/file.js');
    expect($crawled)->not->toContain('https://other.com/page');
});

it('alwaysCrawl bypasses robots.txt', function () {
    $crawled = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com/robots.txt' => "User-agent: *\nDisallow: /blocked",
            'https://example.com' => '<html><body><a href="/blocked/page">Blocked</a><a href="/allowed">Allowed</a></body></html>',
            'https://example.com/blocked/page' => 'Blocked page',
            'https://example.com/allowed' => 'Allowed page',
        ])
        ->respectRobots()
        ->alwaysCrawl(['*/blocked/*'])
        ->onCrawled(function (string $url) use (&$crawled) {
            $crawled[] = $url;
        })
        ->start();

    expect($crawled)->toContain('https://example.com/');
    expect($crawled)->toContain('https://example.com/blocked/page');
    expect($crawled)->toContain('https://example.com/allowed');
});

it('never crawls urls matching neverCrawl patterns', function () {
    $crawled = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body><a href="/page">Page</a><a href="/admin/settings">Admin</a></body></html>',
            'https://example.com/page' => 'Page',
            'https://example.com/admin/settings' => 'Admin',
        ])
        ->ignoreRobots()
        ->neverCrawl(['*/admin/*'])
        ->onCrawled(function (string $url) use (&$crawled) {
            $crawled[] = $url;
        })
        ->start();

    expect($crawled)->toContain('https://example.com/');
    expect($crawled)->toContain('https://example.com/page');
    expect($crawled)->not->toContain('https://example.com/admin/settings');
});

it('alwaysCrawl takes priority over neverCrawl', function () {
    $crawled = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body><a href="/special/page">Special</a></body></html>',
            'https://example.com/special/page' => 'Special page',
        ])
        ->ignoreRobots()
        ->alwaysCrawl(['*/special/*'])
        ->neverCrawl(['*/special/*'])
        ->onCrawled(function (string $url) use (&$crawled) {
            $crawled[] = $url;
        })
        ->start();

    expect($crawled)->toContain('https://example.com/special/page');
});

it('alwaysCrawl bypasses crawl profile', function () {
    $crawled = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body><a href="https://external.com/page">External</a></body></html>',
            'https://external.com/page' => 'External page',
        ])
        ->ignoreRobots()
        ->crawlProfile(new CrawlInternalUrls('https://example.com'))
        ->alwaysCrawl(['https://external.com/*'])
        ->onCrawled(function (string $url) use (&$crawled) {
            $crawled[] = $url;
        })
        ->start();

    expect($crawled)->toContain('https://external.com/page');
});
