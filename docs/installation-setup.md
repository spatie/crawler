---
title: Installation & setup
weight: 3
---

This package can be installed via Composer:

```bash
composer require spatie/crawler
```

No additional setup is needed. The crawler works out of the box.

If you want to crawl JavaScript rendered pages, you'll need a JavaScript renderer. The crawler ships with two built-in renderers: `BrowsershotRenderer` and `CloudflareRenderer`. You can also create your own by implementing the `JavaScriptRenderer` interface. See the [JavaScript rendering](/docs/crawler/v9/advanced-usage/rendering-javascript) page for details.
