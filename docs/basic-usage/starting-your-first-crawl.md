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
        echo "{$url} - {$response->status()}\n";
    })
    ->start();
```

The following callbacks are available:

```php
use GuzzleHttp\Exception\RequestException;
use Spatie\Crawler\Enums\ResourceType;

Crawler::create('https://example.com')
    ->onWillCrawl(function (string $url, ?string $linkText, ?ResourceType $resourceType) {
        // called before a URL is crawled
    })
    ->onCrawled(function (string $url, CrawlResponse $response, ?ResourceType $resourceType) {
        // called for every successfully crawled URL
    })
    ->onFailed(function (string $url, RequestException $e, ?ResourceType $resourceType) {
        // called when a URL could not be crawled
    })
    ->onFinished(function () {
        // called when the whole crawl is complete
    })
    ->start();
```

The `$resourceType` parameter tells you what kind of resource was crawled. It is `null` for the start URL. See [extracting resources](/docs/crawler/v9/configuring-the-crawler/extracting-resources) for more details.

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
