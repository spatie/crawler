---
title: Setting cookies
weight: 15
---

You can send cookies with every request using the `cookies` method. This is useful when you need to crawl a site that requires a session cookie or other cookie based authentication.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->cookies(['session_id' => 'abc123', 'token' => 'xyz'], 'example.com')
    ->start();
```

The first argument is an array of cookie names and values. The second argument is the domain the cookies belong to.
