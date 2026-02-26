---
title: Setting extra headers
weight: 4
---

You can add extra headers to every request the crawler makes using the `headers` method. This is useful when you need to send an authorization token, a custom accept language, or any other header.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->headers([
        'Accept-Language' => 'en-US',
        'X-Custom-Header' => 'value',
    ])
    ->start();
```

The headers will be merged with the default headers. If you need to send a bearer token, you can do so like this:

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->headers([
        'Authorization' => 'Bearer your-token',
    ])
    ->start();
```

You can call `headers` multiple times. Each call will merge the new headers with the previously set ones.
