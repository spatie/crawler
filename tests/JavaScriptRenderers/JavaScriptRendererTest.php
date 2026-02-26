<?php

use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlResponse;
use Spatie\Crawler\JavaScriptRenderers\JavaScriptRenderer;

it('can use a custom javascript renderer', function () {
    $renderer = new class implements JavaScriptRenderer
    {
        public function getRenderedHtml(string $url): string
        {
            return '<html><body><a href="/js-page">JS Page</a></body></html>';
        }
    };

    $crawled = [];

    Crawler::create('https://example.com')
        ->fake([
            'https://example.com' => '<html><body>No JS</body></html>',
            'https://example.com/js-page' => '<html><body>JS rendered page</body></html>',
        ])
        ->ignoreRobots()
        ->executeJavaScript($renderer)
        ->onCrawled(function (string $url, CrawlResponse $response) use (&$crawled) {
            $crawled[] = $url;
        })
        ->start();

    expect($crawled)->toContain('https://example.com/');
    expect($crawled)->toContain('https://example.com/js-page');
});

it('can disable javascript execution', function () {
    $crawler = Crawler::create('https://example.com');

    $renderer = new class implements JavaScriptRenderer
    {
        public function getRenderedHtml(string $url): string
        {
            return '<html></html>';
        }
    };

    $crawler->executeJavaScript($renderer);
    expect($crawler->mayExecuteJavascript())->toBeTrue();

    $crawler->doNotExecuteJavaScript();
    expect($crawler->mayExecuteJavascript())->toBeFalse();
});
