---
title: Setting the default scheme
weight: 9
---

By default, URLs without a scheme are prefixed with `https`. You can change this using the `defaultScheme` method.

```php
use Spatie\Crawler\Crawler;

Crawler::create('example.com')
    ->defaultScheme('http')
    ->start();
```
