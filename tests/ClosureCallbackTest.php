<?php

use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlResponse;
use Spatie\Crawler\Test\TestClasses\CrawlLogger;
use Spatie\Crawler\Test\TestClasses\Log;

it('can use onWillCrawl closure', function () {
    $willCrawl = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body><a href="/about">About</a></body></html>',
            'https://example.com/about' => '<html><body>About</body></html>',
        ])
        ->ignoreRobots()
        ->onWillCrawl(function (string $url, ?string $linkText) use (&$willCrawl) {
            $willCrawl[] = ['url' => $url, 'linkText' => $linkText];
        })
        ->start();

    expect($willCrawl)
        ->toHaveCount(2)
        ->{0}->url->toBe('https://example.com/')
        ->{1}->url->toBe('https://example.com/about')
        ->{1}->linkText->toBe('About');
});

it('can use onCrawled closure', function () {
    $crawled = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body>Hello</body></html>',
        ])
        ->ignoreRobots()
        ->onCrawled(function (string $url, CrawlResponse $response) use (&$crawled) {
            $crawled[] = $url;
        })
        ->start();

    expect($crawled)->toContain('https://example.com/');
});

it('can use onFinished closure', function () {
    $finished = false;

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body>Hello</body></html>',
        ])
        ->ignoreRobots()
        ->onFinished(function () use (&$finished) {
            $finished = true;
        })
        ->start();

    expect($finished)->toBeTrue();
});

it('can stack multiple onCrawled closures', function () {
    $firstCrawled = [];
    $secondCrawled = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body>Hello</body></html>',
        ])
        ->ignoreRobots()
        ->onCrawled(function (string $url, CrawlResponse $response) use (&$firstCrawled) {
            $firstCrawled[] = $url;
        })
        ->onCrawled(function (string $url, CrawlResponse $response) use (&$secondCrawled) {
            $secondCrawled[] = $url;
        })
        ->start();

    expect($firstCrawled)->toContain('https://example.com/');
    expect($secondCrawled)->toContain('https://example.com/');
});

it('can mix closures and observers', function () {
    $crawledFromClosure = [];

    Log::reset();

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body>Hello</body></html>',
        ])
        ->ignoreRobots()
        ->addObserver(new CrawlLogger)
        ->onCrawled(function (string $url, CrawlResponse $response) use (&$crawledFromClosure) {
            $crawledFromClosure[] = $url;
        })
        ->start();

    expect($crawledFromClosure)->toContain('https://example.com/');
    expect(Log::getContents())->toContain('hasBeenCrawled: https://example.com/');
});
