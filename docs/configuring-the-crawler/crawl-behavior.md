---
title: Crawl behavior
weight: 1
---

## Concurrency

To improve the speed of the crawl, the package concurrently crawls 10 URLs by default. You can change this number using the `concurrency` method.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->concurrency(1) // crawl URLs one by one
    ->start();
```

## Request delay

By default, there is no delay between requests. In some cases you might get rate limited when crawling too aggressively. You can add a pause between every request using the `delay` method. The value is expressed in milliseconds.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->delay(150) // wait 150ms after every page
    ->start();
```

## Throttling

For more control over request pacing, you can use a throttle. A throttle is a class that implements `Spatie\Crawler\Throttlers\Throttle`. When a throttle is set, it takes precedence over the `delay` method.

### Fixed delay

The `FixedDelayThrottle` works like `delay()`, but as a class you can pass around and configure independently.

```php
use Spatie\Crawler\Crawler;
use Spatie\Crawler\Throttlers\FixedDelayThrottle;

Crawler::create('https://example.com')
    ->throttle(new FixedDelayThrottle(delayMs: 150))
    ->start();
```

### Adaptive throttle

The `AdaptiveThrottle` adjusts the delay based on how fast the server responds. When the server is slow, the crawler backs off. When it speeds up, the delay decreases. You can configure minimum and maximum bounds.

```php
use Spatie\Crawler\Crawler;
use Spatie\Crawler\Throttlers\AdaptiveThrottle;

Crawler::create('https://example.com')
    ->throttle(new AdaptiveThrottle(
        minDelayMs: 50,
        maxDelayMs: 5000,
    ))
    ->start();
```

The delay is calculated as an exponential moving average: `(currentDelay + latency) / 2`, clamped to the configured bounds.

### Custom throttle

You can create your own throttle by implementing the `Throttle` interface:

```php
use Spatie\Crawler\Throttlers\Throttle;

class MyThrottle implements Throttle
{
    public function sleep(): void
    {
        // Called after each response. Pause here.
    }

    public function recordResponseTime(float $seconds): void
    {
        // Called with the transfer time of each response.
    }
}
```

## Crawl depth

By default, the crawler continues until it has crawled every reachable page. You can limit how deep the crawler will go using the `depth` method.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->depth(2)
    ->start();
```

A depth of 0 means only the start URL will be crawled. A depth of 1 means the start URL and any pages it links to, and so on.

## Default scheme

By default, URLs without a scheme are prefixed with `https`. You can change this using the `defaultScheme` method.

```php
use Spatie\Crawler\Crawler;

Crawler::create('example.com')
    ->defaultScheme('http')
    ->start();
```
