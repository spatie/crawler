---
title: Setting timeouts
weight: 5
---

By default, the crawler uses a 10 second timeout for both connecting and receiving a response. You can change these values using the `connectTimeout` and `requestTimeout` methods. Both accept a value in seconds.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->connectTimeout(5)
    ->requestTimeout(30)
    ->start();
```

The `connectTimeout` method sets the maximum number of seconds to wait while trying to connect to the server. The `requestTimeout` method sets the maximum number of seconds to wait for the entire request (including the response) to complete.
