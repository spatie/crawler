---
title: Collecting URLs
weight: 3
---

The most common use case is to collect all URLs on a site. The `collectUrls()` method makes this easy:

```php
use Spatie\Crawler\Crawler;

$urls = Crawler::create('https://example.com')
    ->internalOnly()
    ->depth(3)
    ->collectUrls();
```

This returns a `Collection` of `CrawledUrl` objects. Each `CrawledUrl` has these properties:

```php
foreach ($urls as $crawledUrl) {
    $crawledUrl->url;        // string
    $crawledUrl->status;     // int (HTTP status code, or 0 if failed)
    $crawledUrl->foundOnUrl;  // ?string
    $crawledUrl->depth;      // int
}
```

Any observers or closure callbacks you've registered will still be called alongside the URL collection.
