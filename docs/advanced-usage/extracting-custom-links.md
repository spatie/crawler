---
title: Custom link extraction
weight: 2
---

You can customize how links are extracted from a page by creating a class that implements the `UrlParser` interface. The `extractUrls` method should return an array of `ExtractedUrl` objects:

```php
use Spatie\Crawler\Enums\ResourceType;
use Spatie\Crawler\ExtractedUrl;
use Spatie\Crawler\UrlParsers\UrlParser;

class MyUrlParser implements UrlParser
{
    /** @return array<int, ExtractedUrl> */
    public function extractUrls(string $html, string $baseUrl): array
    {
        // parse the HTML and return an array of discovered URLs
        return [
            new ExtractedUrl(
                url: 'https://example.com/page',
                linkText: 'Example page',
                resourceType: ResourceType::Link,
            ),
        ];
    }
}
```

Each `ExtractedUrl` has the following properties:

- `url`: the discovered URL
- `linkText`: the text content of the link (optional)
- `resourceType`: the type of resource (`Link`, `Image`, `Script`, `Stylesheet`, or `OpenGraphImage`)
- `malformedReason`: if set, the URL is treated as malformed and will be skipped

By default, the `LinkUrlParser` is used. It extracts URLs from `<a>` tags, `<link rel="next/prev">`, and `<link hreflang>` elements. When [resource extraction](/docs/crawler/v9/configuring-the-crawler/extracting-resources) is enabled, it also extracts images, scripts, stylesheets, and Open Graph images.

## Crawling sitemaps

There is a built-in option to parse sitemaps instead of (or in addition to) following links. It supports sitemap index files.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->parseSitemaps()
    ->start();
```
