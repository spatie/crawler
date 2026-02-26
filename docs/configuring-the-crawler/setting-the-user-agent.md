---
title: Setting the user agent
weight: 3
---

By default, the crawler identifies itself as `*`. You can set a custom user agent using the `userAgent` method.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->userAgent('MyBot/1.0')
    ->start();
```

The user agent is also used when checking `robots.txt` rules, so make sure it matches any user agent specific rules you want to respect.
