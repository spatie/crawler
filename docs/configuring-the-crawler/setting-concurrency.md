---
title: Setting concurrency
weight: 1
---

To improve the speed of the crawl, the package concurrently crawls 10 URLs by default. You can change this number using the `concurrency` method.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->concurrency(1) // crawl URLs one by one
    ->start();
```
