---
title: General settings
weight: 1
---

The crawler provides fluent methods to configure its behavior. Each method also has a longer form for backward compatibility.

## Concurrency

To improve the speed of the crawl, the package concurrently crawls 10 URLs by default. You can change this number using the `concurrency` method.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->concurrency(1) // crawl URLs one by one
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

## Request delay

In some cases you might get rate limited when crawling too aggressively. You can add a pause between every request using the `delay` method. The value is expressed in milliseconds.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->delay(150) // wait 150ms after every page
    ->start();
```

## Maximum response size

Most HTML pages are quite small, but the crawler could accidentally pick up on large files such as PDFs and MP3s. To keep memory usage low, the crawler will only use responses that are smaller than 2 MB. If a response becomes larger than 2 MB while streaming, the crawler will stop streaming it and assume an empty response body.

You can change the maximum response size using the `maxResponseSize` method.

```php
use Spatie\Crawler\Crawler;

// Use a 3 MB maximum
Crawler::create('https://example.com')
    ->maxResponseSize(1024 * 1024 * 3)
    ->start();
```

## Allowed MIME types

By default, every found page will be downloaded (up to the maximum response size) and parsed for additional links. You can limit which content types should be downloaded and parsed using the `allowedMimeTypes` method.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->allowedMimeTypes(['text/html', 'text/plain'])
    ->start();
```

This will prevent downloading the body of pages that have different MIME types, like binary files or audio/video that are unlikely to have links embedded in them. This feature mostly saves bandwidth.

## Default scheme

By default, URLs without a scheme are prefixed with `https`. You can change this using the `defaultScheme` method.

```php
use Spatie\Crawler\Crawler;

Crawler::create('example.com')
    ->defaultScheme('http')
    ->start();
```
