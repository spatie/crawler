---
title: Custom crawl queue
weight: 3
---

When crawling a site, the crawler stores URLs to be crawled in a queue. By default, this queue is stored in memory using the built-in `ArrayCrawlQueue`.

When a site is very large you may want to store that queue elsewhere, for example in a database. You can write your own crawl queue by implementing the `Spatie\Crawler\CrawlQueues\CrawlQueue` interface:

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->crawlQueue(new MyCustomQueue())
    ->start();
```

Here are some queue implementations:

- [ArrayCrawlQueue](https://github.com/spatie/crawler/blob/main/src/CrawlQueues/ArrayCrawlQueue.php) (built in, in memory)
- [RedisCrawlQueue](https://github.com/repat/spatie-crawler-redis) (third party)
- [CacheCrawlQueue for Laravel](https://github.com/spekulatius/spatie-crawler-toolkit-for-laravel) (third party)
- [Laravel Model as Queue](https://github.com/insign/spatie-crawler-queue-with-laravel-model) (third party example)
