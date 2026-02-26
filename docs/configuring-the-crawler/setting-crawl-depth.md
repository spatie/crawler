---
title: Setting crawl depth
weight: 7
---

By default, the crawler continues until it has crawled every reachable page. You can limit how deep the crawler will go using the `depth` method.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->depth(2)
    ->start();
```

A depth of 0 means only the start URL will be crawled. A depth of 1 means the start URL and any pages it links to, and so on.
