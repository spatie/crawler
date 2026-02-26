---
title: Disabling SSL verification
weight: 13
---

When crawling sites with self-signed or invalid SSL certificates (for example, a staging environment), you can disable certificate verification using the `withoutVerifying` method.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://staging.example.com')
    ->withoutVerifying()
    ->start();
```

You should only use this for trusted environments. In production, always keep SSL verification enabled.
