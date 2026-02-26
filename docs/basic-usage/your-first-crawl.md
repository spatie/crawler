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

You can also handle failures and get notified when the crawl finishes:

```php
use GuzzleHttp\Exception\RequestException;

Crawler::create('https://example.com')
    ->onCrawled(function (string $url, CrawlResponse $response) {
        // called for every successfully crawled URL
    })
    ->onFailed(function (string $url, RequestException $e) {
        // called when a URL could not be crawled
    })
    ->onFinished(function () {
        // called when the crawl is complete
    })
    ->start();
```

You can register multiple callbacks of the same type. They will all be called in the order they were added.
