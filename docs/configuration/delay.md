---
title: Request delay
weight: 4
---

In some cases you might get rate limited when crawling too aggressively. You can add a pause between every request. The value is expressed in milliseconds.

```php
Crawler::create('https://example.com')
    ->delay(150) // wait 150ms after every page
    ->start();
```

The long form `setDelayBetweenRequests()` also works.
