---
title: Filtering URLs
weight: 5
---

By default, the crawler will crawl every URL it finds, including links to external sites. You can control which URLs are crawled using scope helpers or custom crawl profiles.

## Scope helpers

The simplest way to filter URLs is with the built-in scope helpers:

```php
use Spatie\Crawler\Crawler;

// Only crawl URLs on the same host
Crawler::create('https://example.com')
    ->internalOnly()
    ->start();

// Crawl URLs on the same host and its subdomains
Crawler::create('https://example.com')
    ->includeSubdomains()
    ->start();
```

## Inline filtering

For custom filtering logic, use the `shouldCrawl` method with a closure:

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->shouldCrawl(function (string $url) {
        return !str_contains($url, '/admin');
    })
    ->start();
```

## Custom crawl profiles

For reusable filtering logic, create a class that implements `Spatie\Crawler\CrawlProfiles\CrawlProfile`:

```php
use Spatie\Crawler\CrawlProfiles\CrawlProfile;

class MyCustomProfile implements CrawlProfile
{
    public function shouldCrawl(string $url): bool
    {
        return parse_url($url, PHP_URL_HOST) === 'example.com'
            && !str_contains($url, '/private');
    }
}
```

Then pass it to the crawler:

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->setCrawlProfile(new MyCustomProfile())
    ->start();
```

This package comes with three built-in profiles:

- `CrawlAllUrls`: crawls all URLs on all pages, including external sites (this is the default)
- `CrawlInternalUrls`: only crawls URLs on the same host
- `CrawlSubdomains`: crawls URLs on the same host and its subdomains
