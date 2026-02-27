---
title: Crawl responses
weight: 4
---

When a URL is successfully crawled, your callback or observer receives a `CrawlResponse` object. This provides a friendlier API than the raw PSR-7 response:

```php
use Spatie\Crawler\CrawlResponse;

$response->status();        // int (HTTP status code)
$response->body();          // string (the response body, cached)
$response->header('Name');  // ?string (a single header value)
$response->headers();       // array (all headers)
$response->dom();           // Symfony DomCrawler instance
$response->isSuccessful();  // bool (2xx status)
$response->isRedirect();    // bool (3xx status)
$response->foundOnUrl();    // ?string (the URL where this link was found)
$response->linkText();      // ?string (the anchor text of the link)
$response->depth();         // int (how deep this page is from the start URL)
$response->resourceType();  // ResourceType (link, image, script, etc.)
$response->transferStats(); // ?Spatie\Crawler\TransferStatistics (transfer timing and metadata, null for faked responses)
```

If you need access to the underlying PSR-7 response:

```php
$response->toPsrResponse(); // Psr\Http\Message\ResponseInterface
```

## Transfer statistics

Each response includes a `Spatie\Crawler\TransferStatistics` object with typed accessors for timing data and other transfer details:

```php
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlResponse;

Crawler::create('https://example.com')
    ->onCrawled(function (string $url, CrawlResponse $response) {
        $stats = $response->transferStats();

        $stats->transferTimeInMs();   // ?float (total transfer time)
        $stats->effectiveUri();       // UriInterface (final URI after redirects)
    })
    ->start();
```

The `transferStats()` method returns `null` for faked responses.

All timing methods return values in milliseconds. They return `null` when the stat is unavailable (for example, `tlsHandshakeTimeInMs()` will be `null` for plain HTTP requests).

```php
$stats = $response->transferStats();

// Timing (all in milliseconds)
$stats->transferTimeInMs();      // ?float - Total transfer time
$stats->connectionTimeInMs();    // ?float - TCP connection time
$stats->dnsLookupTimeInMs();     // ?float - DNS resolution time
$stats->tlsHandshakeTimeInMs();  // ?float - SSL/TLS handshake time
$stats->timeToFirstByteInMs();   // ?float - Time to first byte (TTFB)
$stats->redirectTimeInMs();      // ?float - Time spent on redirects

// Other
$stats->effectiveUri();                    // UriInterface - Final URI after redirects
$stats->primaryIp();                       // ?string - IP address of the server
$stats->downloadSpeedInBytesPerSecond();   // ?float - Average download speed
$stats->requestSizeInBytes();              // ?int - Size of the HTTP request
```

## Using the DOM crawler

The `dom()` method returns a [Symfony DomCrawler](https://symfony.com/doc/current/components/dom_crawler.html) instance, which makes it easy to extract structured data from pages:

```php
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlResponse;

Crawler::create('https://example.com')
    ->onCrawled(function (string $url, CrawlResponse $response) {
        $title = $response->dom()->filter('title')->text('');
        $h1 = $response->dom()->filter('h1')->text('');

        echo "{$url}: {$title}\n";
    })
    ->start();
```
