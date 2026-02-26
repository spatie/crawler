# Upgrading

## From v7 to v8

v8 is a major rewrite that simplifies the API, removes dependencies, and adds built-in testability. Below is a complete list of breaking changes.

### Entry point

```php
// Before
Crawler::create()->startCrawling('https://example.com');

// After
Crawler::create('https://example.com')->start();
```

### CrawlObserver signatures

All `UriInterface` parameters have been replaced with plain `string` URLs. The `ResponseInterface` parameter in `crawled()` is now a `CrawlResponse` object.

```php
// Before
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

public function willCrawl(UriInterface $url, ?string $linkText): void {}

public function crawled(
    UriInterface $url,
    ResponseInterface $response,
    ?UriInterface $foundOnUrl = null,
    ?string $linkText = null,
): void {}

public function crawlFailed(
    UriInterface $url,
    RequestException $requestException,
    ?UriInterface $foundOnUrl = null,
    ?string $linkText = null,
): void {}

// After
use Spatie\Crawler\CrawlResponse;

public function willCrawl(string $url, ?string $linkText): void {}

public function crawled(
    string $url,
    CrawlResponse $response,
    ?string $foundOnUrl = null,
    ?string $linkText = null,
): void {}

public function crawlFailed(
    string $url,
    RequestException $requestException,
    ?string $foundOnUrl = null,
    ?string $linkText = null,
): void {}
```

The `CrawlResponse` object provides a friendlier API than the raw PSR-7 response:

```php
$response->status();        // int
$response->body();          // string (cached)
$response->header('Name');  // ?string
$response->headers();       // array
$response->dom();           // Symfony DomCrawler instance
$response->isSuccessful();  // bool
$response->isRedirect();    // bool
$response->foundOnUrl();    // ?string
$response->linkText();      // ?string
$response->depth();         // int
$response->toPsrResponse(); // ResponseInterface (if you still need it)
```

### CrawlProfile is now an interface

```php
// Before
use Psr\Http\Message\UriInterface;

class MyCrawlProfile extends CrawlProfile
{
    public function shouldCrawl(UriInterface $url): bool
    {
        return $url->getHost() === 'example.com';
    }
}

// After
class MyCrawlProfile implements CrawlProfile
{
    public function shouldCrawl(string $url): bool
    {
        return parse_url($url, PHP_URL_HOST) === 'example.com';
    }
}
```

### CrawlUrl uses strings

`CrawlUrl::$url` and `CrawlUrl::$foundOnUrl` are now `string` and `?string` instead of `UriInterface` and `?UriInterface`. A new `int $depth` property tracks crawl depth.

```php
// Before
CrawlUrl::create(new Uri('https://example.com'), new Uri('https://example.com/page'));

// After
CrawlUrl::create('https://example.com', 'https://example.com/page');
```

### CrawlQueue interface

The `has()` method now accepts a `string` instead of `CrawlUrl|UriInterface`.

```php
// Before
public function has(CrawlUrl|UriInterface $crawlUrl): bool;

// After
public function has(string $url): bool;
```

### UrlParser interface

The `UrlParser` interface has been redesigned. It no longer receives a `Crawler` instance in the constructor and no longer adds URLs to the queue directly. Instead, it returns an array of discovered URLs.

```php
// Before
interface UrlParser
{
    public function __construct(Crawler $crawler);
    public function addFromHtml(string $html, UriInterface $foundOnUrl, ?UriInterface $originalUrl = null): void;
}

// After
interface UrlParser
{
    /** @return array<string, ?string> url => linkText */
    public function extractUrls(string $html, string $baseUrl): array;
}
```

If you used `setUrlParserClass()` with `SitemapUrlParser`, use `parseSitemaps()` instead:

```php
// Before
$crawler->setUrlParserClass(SitemapUrlParser::class);

// After
$crawler->parseSitemaps();
```

### Default scheme changed to HTTPS

URLs without a scheme now default to `https` instead of `http`.

```php
// Before: 'example.com' became 'http://example.com'
// After: 'example.com' becomes 'https://example.com'

// To restore the old behavior:
Crawler::create('example.com')->defaultScheme('http')->start();
```

### JavaScript rendering is now driver-based

Browsershot is no longer a required dependency. It has been moved to `suggest`. The `executeJavaScript()` method now optionally accepts a `JavaScriptRenderer` instance.

```php
// Before
$crawler->setBrowsershot($browsershot);
$crawler->executeJavaScript();

// After (Browsershot is still the default if installed)
$crawler->executeJavaScript();

// Or with a custom renderer
$crawler->executeJavaScript(new BrowsershotRenderer($browsershot));
$crawler->executeJavaScript(new CloudflareRenderer($endpoint));
```

The `setBrowsershot()` and `getBrowsershot()` methods have been removed. To configure Browsershot, pass a configured instance to `BrowsershotRenderer`:

```php
$browsershot = (new Browsershot)->noSandbox()->waitUntilNetworkIdle();
$crawler->executeJavaScript(new BrowsershotRenderer($browsershot));
```

### Removed dependencies

The `nicmart/tree` package is no longer used. Depth tracking is now handled with a simple `int $depth` property on `CrawlUrl`. The `spatie/browsershot` package has been moved from `require` to `suggest`.

### Removed classes

- `Spatie\Crawler\Url` (was a `Uri` subclass with link text, no longer needed)
- `Spatie\Crawler\ResponseWithCachedBody` (replaced by `CrawlResponse`)

### CrawlObserverCollection

`CrawlObserverCollection` no longer implements `ArrayAccess` or `Iterator`. If you were iterating over the collection directly, use the `addObserver()` method instead.

### New features (non-breaking)

These are new additions that do not require any changes to existing code.

**Closure callbacks** as an alternative to observer classes:

```php
Crawler::create('https://example.com')
    ->onCrawled(function (string $url, CrawlResponse $response) {
        echo $url . ' - ' . $response->status();
    })
    ->onFailed(function (string $url, RequestException $e) { ... })
    ->onFinished(function () { ... })
    ->start();
```

**`collectUrls()`** for the most common use case:

```php
$urls = Crawler::create('https://example.com')
    ->internalOnly()
    ->depth(3)
    ->collectUrls(); // Returns Collection<CrawledUrl>
```

**`fake()`** for testing without an HTTP server:

```php
Crawler::create('https://example.com')
    ->fake([
        'https://example.com' => '<html><a href="/about">About</a></html>',
        'https://example.com/about' => '<html>About page</html>',
    ])
    ->collectUrls();
```

**Scope helpers** for common crawl profiles:

```php
$crawler->internalOnly();       // Same as crawlProfile(new CrawlInternalUrls(...))
$crawler->includeSubdomains();  // Same as crawlProfile(new CrawlSubdomains(...))
$crawler->shouldCrawl(fn (string $url) => ...); // Inline profile
```

**Shorter method names**:

```php
$crawler->depth(3);
$crawler->concurrency(10);
$crawler->delay(100);
$crawler->userAgent('Bot');
$crawler->limit(500);
```

## From v5 to v6

- There are no breaking changes to the API. Internally, we shuffled around some checks around crawl limit that might affected some edge cases

## From v3 to v4

- The `CrawlObserver` and `CrawlProfile` are upgraded from interfaces to abstract classes, so you have to convert your old observers and profiles. `crawled` now receives every successfully crawled uri, `crawlFailed` every failed one.

## From v2 to v3

- PHP 7.1 is now required as a minimum version.
- Instead of using our custom `\Spatie\Crawler\Url` object, we're now using the `Psr\Http\Message\UriInterface`. 
Custom Profiles and Observers will need to be changed to have the correct arguments and return types.
We're using `\GuzzleHttp\Psr7\Uri` as the concrete URI implementation.
