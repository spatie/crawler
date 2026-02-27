---
title: Tracking progress
weight: 5
---

The crawler provides real-time progress tracking through the `CrawlProgress` object and reports why a crawl stopped through the `FinishReason` enum.

## CrawlProgress

Every `onCrawled`, `onFailed`, and `onFinished` callback receives a `CrawlProgress` object with the following properties:

```php
use Spatie\Crawler\CrawlProgress;

// Available on every CrawlProgress instance:
$progress->urlsCrawled;   // int (number of URLs successfully crawled)
$progress->urlsFailed;    // int (number of URLs that failed)
$progress->urlsProcessed; // int (urlsCrawled + urlsFailed)
$progress->urlsFound;     // int (total URLs added to the queue)
$progress->urlsPending;   // int (URLs not yet processed)
```

Here's an example that logs progress during a crawl:

```php
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlProgress;
use Spatie\Crawler\CrawlResponse;

Crawler::create('https://example.com')
    ->onCrawled(function (string $url, CrawlResponse $response, CrawlProgress $progress) {
        echo "[{$progress->urlsProcessed}/{$progress->urlsFound}] {$url}\n";
    })
    ->start();
```

## FinishReason

The `start()` method returns a `FinishReason` enum that tells you why the crawl stopped:

```php
use Spatie\Crawler\Crawler;
use Spatie\Crawler\Enums\FinishReason;

$reason = Crawler::create('https://example.com')
    ->limit(100)
    ->start();

match ($reason) {
    FinishReason::Completed => 'All URLs have been crawled',
    FinishReason::CrawlLimitReached => 'Stopped because the crawl limit was reached',
    FinishReason::TimeLimitReached => 'Stopped because the time limit was reached',
    FinishReason::Interrupted => 'Stopped by a signal (SIGINT/SIGTERM)',
};
```

The `onFinished` callback also receives the `FinishReason`:

```php
use Spatie\Crawler\CrawlProgress;
use Spatie\Crawler\Enums\FinishReason;

Crawler::create('https://example.com')
    ->limit(100)
    ->onFinished(function (FinishReason $reason, CrawlProgress $progress) {
        echo "Crawl finished: {$reason->value}\n";
        echo "Crawled {$progress->urlsCrawled} URLs, {$progress->urlsFailed} failed\n";
    })
    ->start();
```

## Using progress in observers

Observer classes receive `CrawlProgress` and `FinishReason` through their method signatures:

```php
use Spatie\Crawler\CrawlObservers\CrawlObserver;
use Spatie\Crawler\CrawlProgress;
use Spatie\Crawler\CrawlResponse;
use Spatie\Crawler\Enums\FinishReason;

class ProgressLogger extends CrawlObserver
{
    public function crawled(
        string $url,
        CrawlResponse $response,
        CrawlProgress $progress,
    ): void {
        echo "[{$progress->urlsProcessed}/{$progress->urlsFound}] {$url}\n";
    }

    public function finishedCrawling(FinishReason $reason, CrawlProgress $progress): void
    {
        echo "Done ({$reason->value}): {$progress->urlsCrawled} crawled, {$progress->urlsFailed} failed\n";
    }
}
```
