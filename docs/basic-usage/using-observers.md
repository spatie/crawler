---
title: Using observers
weight: 2
---

For more structured crawl handling, you can create observer classes instead of using closures. An observer must extend `Spatie\Crawler\CrawlObservers\CrawlObserver`:

```php
namespace App;

use GuzzleHttp\Exception\RequestException;
use Spatie\Crawler\CrawlObservers\CrawlObserver;
use Spatie\Crawler\CrawlProgress;
use Spatie\Crawler\CrawlResponse;
use Spatie\Crawler\Enums\FinishReason;
use Spatie\Crawler\Enums\ResourceType;

class MyCrawlObserver extends CrawlObserver
{
    public function willCrawl(string $url, ?string $linkText, ?ResourceType $resourceType = null): void
    {
        // called before a URL is crawled
    }

    public function crawled(
        string $url,
        CrawlResponse $response,
        CrawlProgress $progress,
    ): void {
        // called when a URL has been successfully crawled
    }

    public function crawlFailed(
        string $url,
        RequestException $requestException,
        CrawlProgress $progress,
        ?string $foundOnUrl = null,
        ?string $linkText = null,
        ?ResourceType $resourceType = null,
    ): void {
        // called when a URL could not be crawled
    }

    public function finishedCrawling(FinishReason $reason, CrawlProgress $progress): void
    {
        // called when the entire crawl is complete
    }
}
```

Pass the observer to the crawler:

```php
Crawler::create('https://example.com')
    ->addObserver(new MyCrawlObserver())
    ->start();
```

The `crawled()` method receives a `CrawlProgress` object with live crawl statistics and a `CrawlResponse` that provides access to `foundOnUrl()`, `linkText()`, `resourceType()`, and other response data. See [tracking progress](/docs/crawler/v9/basic-usage/tracking-progress) for more on `CrawlProgress`.

The `crawlFailed()` method also includes `$foundOnUrl`, `$linkText`, and `$resourceType` parameters since there is no `CrawlResponse` available for failed requests.

## Using multiple observers

You can add multiple observers. They will all be notified of every crawl event:

```php
Crawler::create('https://example.com')
    ->addObserver(new LoggingObserver())
    ->addObserver(new MetricsObserver())
    ->start();
```

You can also combine observers with closure callbacks. Both will be called:

```php
Crawler::create('https://example.com')
    ->addObserver(new MyObserver())
    ->onCrawled(function (string $url, CrawlResponse $response) {
        // this will also be called alongside the observer
    })
    ->start();
```
