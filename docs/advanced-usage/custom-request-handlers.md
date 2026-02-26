---
title: Custom request handlers
weight: 3
---

The crawler uses two handler classes to process Guzzle pool results: one for fulfilled requests and one for failed requests. You can replace these with your own subclasses to customize how responses and errors are processed.

## Custom fulfilled handler

Create a class that extends `CrawlRequestFulfilled`:

```php
use Psr\Http\Message\ResponseInterface;
use Spatie\Crawler\Handlers\CrawlRequestFulfilled;

class MyFulfilledHandler extends CrawlRequestFulfilled
{
    public function __invoke(ResponseInterface $response, $index)
    {
        // your custom logic here

        parent::__invoke($response, $index);
    }
}
```

Then pass it to the crawler:

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->fulfilledHandler(MyFulfilledHandler::class)
    ->start();
```

## Custom failed handler

Create a class that extends `CrawlRequestFailed`:

```php
use Exception;
use Spatie\Crawler\Handlers\CrawlRequestFailed;

class MyFailedHandler extends CrawlRequestFailed
{
    public function __invoke(Exception $exception, $index)
    {
        // your custom logic here

        parent::__invoke($exception, $index);
    }
}
```

Then pass it to the crawler:

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->failedHandler(MyFailedHandler::class)
    ->start();
```

The class you pass must extend the base handler class. If it doesn't, an `InvalidCrawlRequestHandler` exception will be thrown.
