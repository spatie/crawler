---
title: Setting a proxy
weight: 14
---

You can route all crawler requests through a proxy server using the `proxy` method.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->proxy('http://proxy-server:8080')
    ->start();
```

This accepts any proxy string supported by Guzzle, including authenticated proxies.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->proxy('http://username:password@proxy-server:8080')
    ->start();
```
