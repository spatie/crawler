---
title: Crawl limits
weight: 2
---

By default, the crawler continues until it has crawled every page it can find. This behavior might cause issues if you are working in an environment with limitations such as a serverless environment.

The crawl behavior can be controlled with these options:

- `limit()`: the maximum number of URLs to crawl across all executions
- `limitPerExecution()`: how many URLs to process during the current crawl
- `timeLimit()`: the maximum execution time across all executions
- `timeLimitPerExecution()`: the maximum execution time for the current crawl

## Using the total crawl limit

The `limit()` method allows you to limit the total number of URLs to crawl, no matter how often you call the crawler.

```php
use Spatie\Crawler\Crawler;

$queue = <your queue implementation>;

// Crawls 5 URLs and ends.
Crawler::create('https://example.com')
    ->setCrawlQueue($queue)
    ->limit(5)
    ->start();

// Doesn't crawl further as the total limit is reached.
Crawler::create('https://example.com')
    ->setCrawlQueue($queue)
    ->limit(5)
    ->start();
```

## Using the current crawl limit

The `limitPerExecution()` method limits how many URLs will be crawled in a single execution. This code will process 5 pages with each execution, without a total limit of pages to crawl.

```php
use Spatie\Crawler\Crawler;

$queue = <your queue implementation>;

// Crawls 5 URLs and ends.
Crawler::create('https://example.com')
    ->setCrawlQueue($queue)
    ->limitPerExecution(5)
    ->start();

// Crawls the next 5 URLs and ends.
Crawler::create('https://example.com')
    ->setCrawlQueue($queue)
    ->limitPerExecution(5)
    ->start();
```

## Combining limits

Both limits can be combined to control the crawler:

```php
use Spatie\Crawler\Crawler;

$queue = <your queue implementation>;

// Crawls 5 URLs and ends.
Crawler::create('https://example.com')
    ->setCrawlQueue($queue)
    ->limit(10)
    ->limitPerExecution(5)
    ->start();

// Crawls the next 5 URLs and ends.
Crawler::create('https://example.com')
    ->setCrawlQueue($queue)
    ->limit(10)
    ->limitPerExecution(5)
    ->start();

// Doesn't crawl further as the total limit is reached.
Crawler::create('https://example.com')
    ->setCrawlQueue($queue)
    ->limit(10)
    ->limitPerExecution(5)
    ->start();
```
