---
title: Adding a request delay
weight: 2
---

In some cases you might get rate limited when crawling too aggressively. You can add a pause between every request using the `delay` method. The value is expressed in milliseconds.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->delay(150) // wait 150ms after every page
    ->start();
```
