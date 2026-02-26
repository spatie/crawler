---
title: Setting query parameters
weight: 16
---

You can append query parameters to every request the crawler makes using the `queryParameters` method. This is useful for passing API keys or other parameters that need to be present on every request.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->queryParameters(['api_key' => 'your-key'])
    ->start();
```

You can call `queryParameters` multiple times. Each call will merge the new parameters with the previously set ones.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->queryParameters(['api_key' => 'your-key'])
    ->queryParameters(['lang' => 'en'])
    ->start();
```
