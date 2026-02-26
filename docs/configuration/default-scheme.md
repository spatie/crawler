---
title: Default scheme
weight: 9
---

By default, URLs without a scheme are prefixed with `https`. You can change this:

```php
Crawler::create('example.com')
    ->defaultScheme('http')
    ->start();
```

The long form `setDefaultScheme()` also works.
