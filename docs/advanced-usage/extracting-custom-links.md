---
title: Custom link extraction
weight: 2
---

You can customize how links are extracted from a page by creating a class that implements the `UrlParser` interface:

```php
use Spatie\Crawler\UrlParsers\UrlParser;

class MyUrlParser implements UrlParser
{
    /** @return array<string, ?string> url => linkText */
    public function extractUrls(string $html, string $baseUrl): array
    {
        // parse the HTML and return an array of discovered URLs
        // keys are the URLs, values are the link text (or null)
    }
}
```

By default, the `LinkUrlParser` is used. It extracts all links from the `href` attribute of `<a>` tags.

## Crawling sitemaps

There is a built-in option to parse sitemaps instead of (or in addition to) following links. It supports sitemap index files.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->parseSitemaps()
    ->start();
```
