---
title: Crawling across requests
weight: 4
---

You can use `limitPerExecution()` to break up long running crawls across multiple HTTP requests. This is useful in serverless environments or when you want to avoid timeouts.

## Initial request

To start crawling across different requests, create a queue instance and pass it to the crawler. The crawler will fill the queue as pages are processed and new URLs are discovered. After the crawler finishes (because it hit the per execution limit), serialize and store the queue.

```php
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlQueues\ArrayCrawlQueue;

$queue = new ArrayCrawlQueue(); // or your custom queue

// Crawl the first batch of URLs
Crawler::create('https://example.com')
    ->crawlQueue($queue)
    ->limitPerExecution(10)
    ->start();

// Serialize and store the queue for the next request
$serializedQueue = serialize($queue);
```

## Subsequent requests

For following requests, unserialize the queue and pass it to the crawler:

```php
use Spatie\Crawler\Crawler;

$queue = unserialize($serializedQueue);

// Crawl the next batch of URLs
Crawler::create('https://example.com')
    ->crawlQueue($queue)
    ->limitPerExecution(10)
    ->start();

// Serialize and store the queue again
$serializedQueue = serialize($queue);
```

The behavior is based on the information in the queue. Only if the same queue instance is passed will the crawler continue where it left off. When a completely new queue is passed, the limits of previous crawls won't apply.

A more detailed example can be found in [this repository](https://github.com/spekulatius/spatie-crawler-cached-queue-example).
