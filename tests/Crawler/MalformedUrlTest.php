<?php

use GuzzleHttp\Exception\RequestException;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlProgress;

it('reports malformed urls via crawlFailed', function () {
    $failed = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => 'There is an <a href="https:///AfyaVzw">invalid</a> url and a <a href="/valid">valid</a> link',
            'https://example.com/valid' => 'Valid page',
        ])
        ->ignoreRobots()
        ->onCrawled(function () {})
        ->onFailed(function (string $url, RequestException $exception, CrawlProgress $progress) use (&$failed) {
            $failed[] = ['url' => $url, 'message' => $exception->getMessage()];
        })
        ->start();

    $failedUrls = array_column($failed, 'url');

    expect($failedUrls)->toContain('https:///AfyaVzw');

    $malformed = array_values(array_filter($failed, fn ($item) => $item['url'] === 'https:///AfyaVzw'))[0] ?? null;
    expect($malformed['message'])->toContain('Malformed URL');
});

it('still crawls valid urls when malformed urls are present', function () {
    $crawled = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => 'There is an <a href="https:///AfyaVzw">invalid</a> url and a <a href="/valid">valid</a> link',
            'https://example.com/valid' => 'Valid page',
        ])
        ->ignoreRobots()
        ->onCrawled(function (string $url) use (&$crawled) {
            $crawled[] = $url;
        })
        ->start();

    expect($crawled)->toContain('https://example.com/');
    expect($crawled)->toContain('https://example.com/valid');
});
