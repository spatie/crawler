---
title: Your first crawl
weight: 1
---

The simplest way to start crawling is to use closure callbacks:

```php
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlResponse;

Crawler::create('https://example.com')
    ->onCrawled(function (string $url, CrawlResponse $response) {
        echo "{$url}: {$response->status()}\n";
    })
    ->start();
```

The following callbacks are available:

```php
use GuzzleHttp\Exception\RequestException;
use Spatie\Crawler\CrawlProgress;
use Spatie\Crawler\Enums\FinishReason;
use Spatie\Crawler\Enums\ResourceType;

Crawler::create('https://example.com')
    ->onWillCrawl(function (string $url, ?string $linkText, ?ResourceType $resourceType) {
        // called before a URL is crawled
    })
    ->onCrawled(function (string $url, CrawlResponse $response, CrawlProgress $progress) {
        // called for every successfully crawled URL
    })
    ->onFailed(function (string $url, RequestException $e, CrawlProgress $progress, ?string $foundOnUrl, ?string $linkText) {
        // called when a URL could not be crawled
    })
    ->onFinished(function (FinishReason $reason, CrawlProgress $progress) {
        // called when the whole crawl is complete
    })
    ->start();
```

Each callback (except `onWillCrawl`) receives a `CrawlProgress` object with live crawl statistics. See [tracking progress](/docs/crawler/v9/basic-usage/tracking-progress) for details.

The `onFinished` callback also receives a `FinishReason` enum that tells you why the crawl stopped.

You can register multiple callbacks of the same type. They will all be called in the order they were added.

```php
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlResponse;

Crawler::create('https://example.com')
    ->onCrawled(function (string $url, CrawlResponse $response) {
        // first callback: log to database
    })
    ->onCrawled(function (string $url, CrawlResponse $response) {
        // second callback: send notification
    })
    ->start();
```
