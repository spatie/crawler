---
title: Extracting resources
weight: 2
---

By default, the crawler only extracts links (`<a>` tags and some `<link>` tags) from each page. You can also instruct it to extract images, scripts, stylesheets, and Open Graph images. This is useful for broken asset checking, content auditing, or building a complete inventory of a site's resources.

## Extracting specific resource types

Use the `alsoExtract` method to extract additional resource types alongside links:

```php
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlResponse;
use Spatie\Crawler\Enums\ResourceType;

Crawler::create('https://example.com')
    ->alsoExtract(ResourceType::Image, ResourceType::Stylesheet)
    ->onCrawled(function (string $url, CrawlResponse $response) {
        echo "{$response->resourceType()->value}: {$url}\n";
    })
    ->start();
```

The available resource types are:

| Type | What it extracts |
|------|-----------------|
| `ResourceType::Link` | `<a>` tags, `<link rel="next/prev">`, `<link hreflang>` (always included) |
| `ResourceType::Image` | `<img src>` and `<img data-src>` (lazy loaded images) |
| `ResourceType::Script` | `<script src>` and `<link rel="modulepreload">` |
| `ResourceType::Stylesheet` | `<link rel="stylesheet">`, `<link type="text/css">`, `<link as="style">` |
| `ResourceType::OpenGraphImage` | `<meta property="og:image">` and `<meta property="twitter:image">` |

## Extracting all resource types

To extract everything at once, use `extractAll`:

```php
Crawler::create('https://example.com')
    ->extractAll()
    ->onCrawled(function (string $url, CrawlResponse $response) {
        // $response->resourceType() tells you what kind of resource this is
    })
    ->start();
```

## Resource types in observers

When using observers, the resource type is available through the `CrawlResponse`:

```php
use Spatie\Crawler\CrawlObservers\CrawlObserver;
use Spatie\Crawler\CrawlProgress;
use Spatie\Crawler\CrawlResponse;

class AssetChecker extends CrawlObserver
{
    public function crawled(
        string $url,
        CrawlResponse $response,
        CrawlProgress $progress,
    ): void {
        if ($response->resourceType() === ResourceType::Image && $response->status() === 404) {
            echo "Broken image: {$url} (found on {$response->foundOnUrl()})\n";
        }
    }
}
```

## Base href support

When extracting resources (images, scripts, stylesheets, and Open Graph images), the crawler respects the `<base href>` tag in the HTML. If a page contains `<base href="https://example.com/assets/">`, relative resource URLs will be resolved against that base URL instead of the page URL.

Links (`<a>` tags) also respect `<base href>` through Symfony's DomCrawler.

## Malformed URLs

When the crawler encounters a malformed URL in the HTML (for example, `href="https:///invalid"`), it will report it through your `crawlFailed` callback or observer instead of silently ignoring it. The `RequestException` message will contain the reason the URL could not be parsed.

```php
Crawler::create('https://example.com')
    ->onFailed(function (string $url, RequestException $exception) {
        if (str_contains($exception->getMessage(), 'Malformed URL')) {
            echo "Found malformed URL: {$url}\n";
        }
    })
    ->start();
```

## Resource types in collected URLs

When using `foundUrls()`, each `CrawledUrl` includes the resource type:

```php
$urls = Crawler::create('https://example.com')
    ->extractAll()
    ->foundUrls();

foreach ($urls as $crawledUrl) {
    echo "{$crawledUrl->resourceType->value}: {$crawledUrl->url} ({$crawledUrl->status})\n";
}
```
