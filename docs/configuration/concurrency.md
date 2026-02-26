---
title: Concurrency
weight: 1
---

To improve the speed of the crawl, the package concurrently crawls 10 URLs by default. You can change this number:

```php
Crawler::create('https://example.com')
    ->concurrency(1) // crawl URLs one by one
    ->start();
```

The long form `setConcurrency()` also works.
