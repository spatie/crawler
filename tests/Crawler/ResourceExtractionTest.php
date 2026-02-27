<?php

use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlResponse;
use Spatie\Crawler\Enums\ResourceType;

it('extracts images when configured with alsoExtract', function () {
    $crawled = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body><img src="/logo.png"><a href="/about">About</a></body></html>',
            'https://example.com/about' => '<html><body>About</body></html>',
        ])
        ->ignoreRobots()
        ->alsoExtract(ResourceType::Image)
        ->onCrawled(function (string $url, CrawlResponse $response, ?ResourceType $resourceType) use (&$crawled) {
            $crawled[] = ['url' => $url, 'type' => $resourceType];
        })
        ->start();

    $urls = array_column($crawled, 'url');
    expect($urls)->toContain('https://example.com/');
    expect($urls)->toContain('https://example.com/about');
    expect($urls)->toContain('https://example.com/logo.png');
});

it('extracts scripts when configured with alsoExtract', function () {
    $crawled = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><head><script src="/app.js"></script></head><body>Hello</body></html>',
        ])
        ->ignoreRobots()
        ->alsoExtract(ResourceType::Script)
        ->onCrawled(function (string $url, CrawlResponse $response, ?ResourceType $resourceType) use (&$crawled) {
            $crawled[] = ['url' => $url, 'type' => $resourceType];
        })
        ->start();

    $urls = array_column($crawled, 'url');
    expect($urls)->toContain('https://example.com/app.js');
});

it('extracts stylesheets when configured with alsoExtract', function () {
    $crawled = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><head><link rel="stylesheet" href="/style.css"></head><body>Hello</body></html>',
        ])
        ->ignoreRobots()
        ->alsoExtract(ResourceType::Stylesheet)
        ->onCrawled(function (string $url, CrawlResponse $response, ?ResourceType $resourceType) use (&$crawled) {
            $crawled[] = ['url' => $url, 'type' => $resourceType];
        })
        ->start();

    $urls = array_column($crawled, 'url');
    expect($urls)->toContain('https://example.com/style.css');
});

it('extracts open graph images when configured with alsoExtract', function () {
    $crawled = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><head><meta property="og:image" content="https://example.com/og.jpg"><meta property="twitter:image" content="https://example.com/twitter.jpg"></head><body>Hello</body></html>',
        ])
        ->ignoreRobots()
        ->alsoExtract(ResourceType::OpenGraphImage)
        ->onCrawled(function (string $url, CrawlResponse $response, ?ResourceType $resourceType) use (&$crawled) {
            $crawled[] = ['url' => $url, 'type' => $resourceType];
        })
        ->start();

    $urls = array_column($crawled, 'url');
    expect($urls)->toContain('https://example.com/og.jpg');
    expect($urls)->toContain('https://example.com/twitter.jpg');
});

it('extracts all resource types with extractAll', function () {
    $crawled = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><head><link rel="stylesheet" href="/style.css"><script src="/app.js"></script><meta property="og:image" content="https://example.com/og.jpg"></head><body><img src="/logo.png"><a href="/about">About</a></body></html>',
            'https://example.com/about' => '<html><body>About</body></html>',
        ])
        ->ignoreRobots()
        ->extractAll()
        ->onCrawled(function (string $url, CrawlResponse $response, ?ResourceType $resourceType) use (&$crawled) {
            $crawled[] = ['url' => $url, 'type' => $resourceType];
        })
        ->start();

    $urls = array_column($crawled, 'url');
    expect($urls)->toContain('https://example.com/');
    expect($urls)->toContain('https://example.com/about');
    expect($urls)->toContain('https://example.com/logo.png');
    expect($urls)->toContain('https://example.com/app.js');
    expect($urls)->toContain('https://example.com/style.css');
    expect($urls)->toContain('https://example.com/og.jpg');
});

it('passes resourceType through to observers', function () {
    $resourceTypes = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body><img src="/logo.png"><a href="/about">About</a></body></html>',
            'https://example.com/about' => '<html><body>About</body></html>',
        ])
        ->ignoreRobots()
        ->alsoExtract(ResourceType::Image)
        ->onCrawled(function (string $url, CrawlResponse $response, ?ResourceType $resourceType) use (&$resourceTypes) {
            $resourceTypes[$url] = $resourceType;
        })
        ->start();

    expect($resourceTypes['https://example.com/about'])->toBe(ResourceType::Link);
    expect($resourceTypes['https://example.com/logo.png'])->toBe(ResourceType::Image);
});

it('includes resourceType on collected urls', function () {
    $urls = Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body><img src="/logo.png"><a href="/about">About</a></body></html>',
            'https://example.com/about' => '<html><body>About</body></html>',
        ])
        ->ignoreRobots()
        ->alsoExtract(ResourceType::Image)
        ->collectUrls();

    $image = findUrl($urls, 'https://example.com/logo.png');
    expect($image)->not->toBeNull();
    expect($image->resourceType)->toBe(ResourceType::Image);

    $about = findUrl($urls, 'https://example.com/about');
    expect($about)->not->toBeNull();
    expect($about->resourceType)->toBe(ResourceType::Link);
});

it('extracts images with data-src attribute', function () {
    $crawled = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body><img data-src="/lazy.jpg"></body></html>',
        ])
        ->ignoreRobots()
        ->alsoExtract(ResourceType::Image)
        ->onCrawled(function (string $url) use (&$crawled) {
            $crawled[] = $url;
        })
        ->start();

    expect($crawled)->toContain('https://example.com/lazy.jpg');
});

it('extracts modulepreload links as scripts', function () {
    $crawled = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><head><link rel="modulepreload" href="/module.js"></head><body>Hello</body></html>',
        ])
        ->ignoreRobots()
        ->alsoExtract(ResourceType::Script)
        ->onCrawled(function (string $url, CrawlResponse $response, ?ResourceType $resourceType) use (&$crawled) {
            $crawled[] = ['url' => $url, 'type' => $resourceType];
        })
        ->start();

    $urls = array_column($crawled, 'url');
    expect($urls)->toContain('https://example.com/module.js');

    $module = array_values(array_filter($crawled, fn ($item) => $item['url'] === 'https://example.com/module.js'))[0] ?? null;
    expect($module['type'])->toBe(ResourceType::Script);
});

it('does not extract resources by default', function () {
    $crawled = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><head><link rel="stylesheet" href="/style.css"><script src="/app.js"></script></head><body><img src="/logo.png"><a href="/about">About</a></body></html>',
            'https://example.com/about' => '<html><body>About</body></html>',
        ])
        ->ignoreRobots()
        ->onCrawled(function (string $url) use (&$crawled) {
            $crawled[] = $url;
        })
        ->start();

    expect($crawled)->toContain('https://example.com/');
    expect($crawled)->toContain('https://example.com/about');
    expect($crawled)->not->toContain('https://example.com/style.css');
    expect($crawled)->not->toContain('https://example.com/app.js');
    expect($crawled)->not->toContain('https://example.com/logo.png');
});
