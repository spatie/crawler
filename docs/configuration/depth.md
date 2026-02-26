---
title: Crawl depth
weight: 2
---

By default, the crawler continues until it has crawled every page of the supplied URL. You can limit how deep the crawler will go:

```php
Crawler::create('https://example.com')
    ->depth(2)
    ->start();
```

A depth of 0 means only the start URL will be crawled. A depth of 1 means the start URL and any pages it links to, and so on.

The long form `setMaximumDepth()` also works.
