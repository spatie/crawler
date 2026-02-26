---
title: User agent
weight: 7
---

You can specify a custom User Agent:

```php
Crawler::create('https://example.com')
    ->userAgent('my-agent')
    ->start();
```

The long form `setUserAgent()` also works.

## Robots.txt rules per user agent

When you set a custom User Agent, robots.txt rules specific to that agent will be respected. For example, if your robots.txt contains:

```txt
User-agent: my-agent
Disallow: /
```

The crawler (when using `my-agent` as User Agent) will not crawl any pages on the site.
