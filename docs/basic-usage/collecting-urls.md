---
title: Collecting URLs
weight: 3
---

The most common use case is to collect all URLs on a site. The `foundUrls()` method makes this easy:

```php
use Spatie\Crawler\Crawler;

$urls = Crawler::create('https://example.com')
    ->internalOnly()
    ->depth(3)
    ->foundUrls();
```

This returns an array of `CrawledUrl` objects. Each `CrawledUrl` has these properties:

```php
foreach ($urls as $crawledUrl) {
    $crawledUrl->url;          // string
    $crawledUrl->status;       // int (HTTP status code, or 0 if failed)
    $crawledUrl->foundOnUrl;   // ?string
    $crawledUrl->depth;        // int
    $crawledUrl->resourceType; // ResourceType (link, image, script, etc.)
}
```

The `resourceType` property defaults to `ResourceType::Link`. When you use `alsoExtract()` or `extractAll()`, collected URLs will include the appropriate resource type for each discovered asset. See [extracting resources](/docs/crawler/v9/configuring-the-crawler/extracting-resources) for details.

Any observers or closure callbacks you've registered will still be called alongside the URL collection.
