---
title: Response filtering
weight: 5
---

## Maximum response size

Most HTML pages are quite small, but the crawler could accidentally pick up on large files such as PDFs and MP3s. To keep memory usage low, the crawler will only use responses that are smaller than 2 MB. If a response becomes larger than 2 MB while streaming, the crawler will stop streaming it and assume an empty response body.

You can change the maximum response size using the `maxResponseSizeInBytes` method.

```php
use Spatie\Crawler\Crawler;

// Use a 3 MB maximum
Crawler::create('https://example.com')
    ->maxResponseSizeInBytes(1024 * 1024 * 3)
    ->start();
```

## Allowed MIME types

By default, every found page will be downloaded (up to the maximum response size) and parsed for additional links. You can limit which content types should be downloaded and parsed using the `allowedMimeTypes` method.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->allowedMimeTypes(['text/html', 'text/plain'])
    ->start();
```

This will prevent downloading the body of pages that have different MIME types, like binary files or audio/video that are unlikely to have links embedded in them. This feature mostly saves bandwidth.
