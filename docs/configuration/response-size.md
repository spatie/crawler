---
title: Response size
weight: 5
---

Most HTML pages are quite small, but the crawler could accidentally pick up on large files such as PDFs and MP3s. To keep memory usage low, the crawler will only use responses that are smaller than 2 MB. If a response becomes larger than 2 MB while streaming, the crawler will stop streaming it and assume an empty response body.

You can change the maximum response size:

```php
// Use a 3 MB maximum
Crawler::create('https://example.com')
    ->maxResponseSize(1024 * 1024 * 3)
    ->start();
```

The long form `setMaximumResponseSize()` also works.
