---
title: Setting crawl limits
weight: 2
---

By default, the crawler continues until it has crawled every page it can find. This behavior might cause issues if you are working in an environment with limitations such as a serverless environment.

The crawl behavior can be controlled with these options:

- `limit()`: the maximum number of URLs to crawl across all executions
- `limitPerExecution()`: how many URLs to process during the current crawl
- `timeLimit()`: the maximum execution time in seconds across all executions
- `timeLimitPerExecution()`: the maximum execution time in seconds for the current crawl

## Using the total crawl limit

The `limit()` method allows you to limit the total number of URLs to crawl, no matter how often you call the crawler.

```php
use Spatie\Crawler\Crawler;

$queue = <your queue implementation>;

// Crawls 5 URLs and ends.
Crawler::create('https://example.com')
    ->crawlQueue($queue)
    ->limit(5)
    ->start();

// Doesn't crawl further as the total limit is reached.
Crawler::create('https://example.com')
    ->crawlQueue($queue)
    ->limit(5)
    ->start();
```

## Using the current crawl limit

The `limitPerExecution()` method limits how many URLs will be crawled in a single execution. This is especially useful when [crawling across multiple requests](/docs/crawler/v9/advanced-usage/crawling-across-requests). This code will process 5 pages with each execution, without a total limit of pages to crawl.

```php
use Spatie\Crawler\Crawler;

$queue = <your queue implementation>;

// Crawls 5 URLs and ends.
Crawler::create('https://example.com')
    ->crawlQueue($queue)
    ->limitPerExecution(5)
    ->start();

// Crawls the next 5 URLs and ends.
Crawler::create('https://example.com')
    ->crawlQueue($queue)
    ->limitPerExecution(5)
    ->start();
```

## Using time limits

The `timeLimit()` method sets the maximum execution time across all executions. The `timeLimitPerExecution()` method sets the maximum execution time for a single crawl. Both accept a value in seconds.

```php
use Spatie\Crawler\Crawler;

// Stop crawling after 60 seconds total
Crawler::create('https://example.com')
    ->timeLimit(60)
    ->start();

// Stop each execution after 30 seconds, but allow resuming
Crawler::create('https://example.com')
    ->crawlQueue($queue)
    ->timeLimitPerExecution(30)
    ->start();
```

## Combining limits

All limits can be combined to control the crawler:

```php
use Spatie\Crawler\Crawler;

$queue = <your queue implementation>;

// Crawls 5 URLs and ends.
Crawler::create('https://example.com')
    ->crawlQueue($queue)
    ->limit(10)
    ->limitPerExecution(5)
    ->start();

// Crawls the next 5 URLs and ends.
Crawler::create('https://example.com')
    ->crawlQueue($queue)
    ->limit(10)
    ->limitPerExecution(5)
    ->start();

// Doesn't crawl further as the total limit is reached.
Crawler::create('https://example.com')
    ->crawlQueue($queue)
    ->limit(10)
    ->limitPerExecution(5)
    ->start();
```
