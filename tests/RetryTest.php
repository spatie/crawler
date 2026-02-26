<?php

use Spatie\Crawler\Crawler;

it('can configure retry', function () {
    $crawled = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body><a href="/page">Page</a></body></html>',
            'https://example.com/page' => 'Page content',
        ])
        ->ignoreRobots()
        ->retry(2, 100)
        ->onCrawled(function (string $url) use (&$crawled) {
            $crawled[] = $url;
        })
        ->start();

    expect($crawled)->toContain('https://example.com/');
    expect($crawled)->toContain('https://example.com/page');
});
