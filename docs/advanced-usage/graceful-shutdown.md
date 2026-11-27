---
title: Graceful shutdown
weight: 6
---

When running the crawler as a long-lived CLI process, you may want to stop it cleanly with `Ctrl+C` (SIGINT) or `SIGTERM` instead of killing it mid-request.

The crawler automatically registers signal handlers when the `pcntl` extension is available. When a signal is received:

1. The crawler stops yielding new requests
2. Any in-flight requests complete normally
3. The `finishedCrawling()` method on your observers is called with `FinishReason::Interrupted`
4. The `start()` method returns `FinishReason::Interrupted`

```php
use Spatie\Crawler\Crawler;
use Spatie\Crawler\Enums\FinishReason;

$reason = Crawler::create('https://example.com')
    ->start();

if ($reason === FinishReason::Interrupted) {
    echo "Crawl was interrupted by a signal\n";
}
```

No configuration is needed. This works out of the box on any system where the `pcntl` PHP extension is loaded.

If your application registered its own handler for SIGINT or SIGTERM before the crawl started, that handler still runs when the signal arrives. A Laravel queue worker is an example. The crawler puts your handler back when the crawl ends.

This is especially useful when combining with [crawling across requests](/docs/crawler/v9/advanced-usage/crawling-across-requests). A graceful shutdown ensures that the crawl queue remains in a consistent state, so you can resume crawling later.
