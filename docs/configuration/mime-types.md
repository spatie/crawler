---
title: MIME types
weight: 6
---

By default, every found page will be downloaded (up to the maximum response size) and parsed for additional links. You can limit which content types should be downloaded and parsed:

```php
Crawler::create('https://example.com')
    ->allowedMimeTypes(['text/html', 'text/plain'])
    ->start();
```

This will prevent downloading the body of pages that have different MIME types, like binary files or audio/video that are unlikely to have links embedded in them. This feature mostly saves bandwidth.

The long form `setParseableMimeTypes()` also works.
