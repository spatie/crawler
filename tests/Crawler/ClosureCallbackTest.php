<?php

use GuzzleHttp\Exception\RequestException;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlProgress;
use Spatie\Crawler\CrawlResponse;
use Spatie\Crawler\Enums\FinishReason;
use Spatie\Crawler\Enums\ResourceType;
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
        ->onCrawled(function (string $url, CrawlResponse $response, CrawlProgress $progress) use (&$crawled) {
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
        ->onFinished(function (FinishReason $reason, CrawlProgress $progress) use (&$finished) {
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
        ->onCrawled(function (string $url, CrawlResponse $response, CrawlProgress $progress) use (&$firstCrawled) {
            $firstCrawled[] = $url;
        })
        ->onCrawled(function (string $url, CrawlResponse $response, CrawlProgress $progress) use (&$secondCrawled) {
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
        ->onCrawled(function (string $url, CrawlResponse $response, CrawlProgress $progress) use (&$crawledFromClosure) {
            $crawledFromClosure[] = $url;
        })
        ->start();

    expect($crawledFromClosure)->toContain('https://example.com/');
    expect(Log::getContents())->toContain('hasBeenCrawled: https://example.com/');
});

it('onFailed closure receives foundOnUrl and linkText', function () {
    $failures = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><a href="https:///invalid">Bad link</a></html>',
        ])
        ->ignoreRobots()
        ->onFailed(function (
            string $url,
            RequestException $exception,
            CrawlProgress $progress,
            ?string $foundOnUrl,
            ?string $linkText,
            ?ResourceType $resourceType,
        ) use (&$failures) {
            $failures[] = [
                'url' => $url,
                'foundOnUrl' => $foundOnUrl,
                'linkText' => $linkText,
                'resourceType' => $resourceType,
            ];
        })
        ->start();

    expect($failures)->toHaveCount(1);
    expect($failures[0]['foundOnUrl'])->toBe('https://example.com/');
    expect($failures[0]['linkText'])->toBe('Bad link');
    expect($failures[0]['resourceType'])->toBe(ResourceType::Link);
});
