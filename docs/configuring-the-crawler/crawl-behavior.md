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

In some cases you might get rate limited when crawling too aggressively. You can add a pause between every request using the `delay` method. The value is expressed in milliseconds.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->delay(150) // wait 150ms after every page
    ->start();
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
