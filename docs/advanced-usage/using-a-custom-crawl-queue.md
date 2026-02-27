---
title: Custom crawl queue
weight: 3
---

When crawling a site, the crawler stores URLs to be crawled in a queue. By default, this queue is stored in memory using the built-in `ArrayCrawlQueue`.

## URL normalization

The built-in `ArrayCrawlQueue` normalizes URLs before using them as deduplication keys. This means that `https://Example.com/page` and `https://example.com/page/` are treated as the same URL, preventing redundant requests.

The following normalizations are applied (per RFC 3986):

- Lowercasing scheme and host
- Removing default ports (`:80` for http, `:443` for https)
- Stripping trailing slashes (except for the root `/`)
- Removing empty query strings
- Stripping URL fragments

The original URL is preserved on the `CrawlUrl` object and used for HTTP requests and observer notifications. Only the internal deduplication key uses the normalized form.

If you implement a custom crawl queue, consider applying similar normalizations to avoid crawling duplicate URLs.

When a site is very large you may want to store that queue elsewhere, for example in a database. You can write your own crawl queue by implementing the `Spatie\Crawler\CrawlQueues\CrawlQueue` interface:

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->crawlQueue(new MyCustomQueue())
    ->start();
```

The `CrawlQueue` interface requires the following methods:

```php
interface CrawlQueue
{
    public function add(CrawlUrl $url): self;
    public function has(string $url): bool;
    public function hasPendingUrls(): bool;
    public function getUrlById(mixed $id): CrawlUrl;
    public function getPendingUrl(): ?CrawlUrl;
    public function hasAlreadyBeenProcessed(CrawlUrl $url): bool;
    public function markAsProcessed(CrawlUrl $crawlUrl): void;
    public function getProcessedUrlCount(): int;
    public function getUrlCount(): int;        // total URLs added to the queue
    public function getPendingUrlCount(): int;  // URLs not yet processed
}
```

The `getUrlCount()` and `getPendingUrlCount()` methods are used by the `CrawlProgress` object to report queue statistics. See [tracking progress](/docs/crawler/v9/basic-usage/tracking-progress) for details.

Here are some queue implementations:

- [ArrayCrawlQueue](https://github.com/spatie/crawler/blob/main/src/CrawlQueues/ArrayCrawlQueue.php) (built in, in memory)
- [RedisCrawlQueue](https://github.com/repat/spatie-crawler-redis) (third party)
- [CacheCrawlQueue for Laravel](https://github.com/spekulatius/spatie-crawler-toolkit-for-laravel) (third party)
- [Laravel Model as Queue](https://github.com/insign/spatie-crawler-queue-with-laravel-model) (third party example)
