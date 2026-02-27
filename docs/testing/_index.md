---
title: Testing
weight: 6
---

The crawler provides a `fake()` method that lets you test your crawl logic without making real HTTP requests. Pass an array of URLs mapped to HTML strings:

```php
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlResponse;

$crawled = [];

Crawler::create('https://example.com')
    ->fake([
        'https://example.com' => '<html><body><a href="/about">About</a></body></html>',
        'https://example.com/about' => '<html><body>About page</body></html>',
    ])
    ->onCrawled(function (string $url, CrawlResponse $response) use (&$crawled) {
        $crawled[] = $url;
    })
    ->start();

// $crawled will contain both URLs
```

## How faking works

When `fake()` is used, the crawler replaces Guzzle's HTTP handler with a fake handler that returns responses from the array you provided. URLs not found in the array will return a 404 response.

The fake handler normalizes URLs (handling trailing slashes) and automatically handles `robots.txt` requests. If you don't include a `robots.txt` URL in the fakes array, it returns a 404, which means no restrictions.

## Faking with custom status codes and headers

By default, fake responses return a 200 status with a `text/html` content type. You can use `CrawlResponse::fake()` to create responses with custom status codes and headers:

```php
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlResponse;

Crawler::create('https://example.com')
    ->fake([
        'https://example.com' => '<html><a href="/redirect">Link</a></html>',
        'https://example.com/redirect' => CrawlResponse::fake('', 301, [
            'Location' => 'https://example.com/new-location',
        ]),
        'https://example.com/protected' => CrawlResponse::fake('Forbidden', 403),
        'https://example.com/audio.mp3' => CrawlResponse::fake('audio data', 200, [
            'Content-Type' => 'audio/mpeg',
        ]),
    ])
    ->start();
```

## Faking with foundUrls

The `fake()` method works great with `foundUrls()`:

```php
use Spatie\Crawler\Crawler;

$urls = Crawler::create('https://example.com')
    ->fake([
        'https://example.com' => '<html><a href="/page-1">Page 1</a><a href="/page-2">Page 2</a></html>',
        'https://example.com/page-1' => '<html>Page 1</html>',
        'https://example.com/page-2' => '<html>Page 2</html>',
    ])
    ->internalOnly()
    ->foundUrls();

expect($urls)->toHaveCount(3);
```

## Testing depth limits

```php
use Spatie\Crawler\Crawler;

$urls = Crawler::create('https://example.com')
    ->fake([
        'https://example.com' => '<html><a href="/level-1">Level 1</a></html>',
        'https://example.com/level-1' => '<html><a href="/level-2">Level 2</a></html>',
        'https://example.com/level-2' => '<html>Level 2</html>',
    ])
    ->depth(1)
    ->foundUrls();

// Only the start URL and level-1 will be crawled (depth 0 and 1)
expect($urls)->toHaveCount(2);
```

## Testing finish reasons

You can assert which `FinishReason` was returned by `start()`:

```php
use Spatie\Crawler\Crawler;
use Spatie\Crawler\Enums\FinishReason;

$reason = Crawler::create('https://example.com')
    ->fake([
        'https://example.com' => '<html><a href="/page">Page</a></html>',
        'https://example.com/page' => '<html>Page</html>',
    ])
    ->limit(1)
    ->start();

expect($reason)->toBe(FinishReason::CrawlLimitReached);
```
