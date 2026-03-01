---
title: JavaScript rendering
weight: 1
---

By default, the crawler will not execute JavaScript. You can enable JavaScript rendering using the `executeJavaScript` method.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->executeJavaScript()
    ->start();
```

When called without arguments, the crawler will use `BrowsershotRenderer` which requires [spatie/browsershot](https://github.com/spatie/browsershot) to be installed. If Browsershot is not installed, an exception will be thrown.

## Configuring Browsershot

To customize Browsershot, pass a configured instance to `BrowsershotRenderer`:

```php
use Spatie\Browsershot\Browsershot;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\JavaScriptRenderers\BrowsershotRenderer;

$browsershot = (new Browsershot())
    ->noSandbox()
    ->waitUntilNetworkIdle();

Crawler::create('https://example.com')
    ->executeJavaScript(new BrowsershotRenderer($browsershot))
    ->start();
```

## Using Cloudflare Browser Rendering

The `CloudflareRenderer` uses a Cloudflare Browser Rendering endpoint to render JavaScript. It sends a POST request with the URL and expects a JSON response containing a `content` field with the rendered HTML.

```php
use Spatie\Crawler\Crawler;
use Spatie\Crawler\JavaScriptRenderers\CloudflareRenderer;

Crawler::create('https://example.com')
    ->executeJavaScript(new CloudflareRenderer('https://your-worker.your-domain.workers.dev/render'))
    ->start();
```

You can also pass a custom Guzzle client to the renderer:

```php
use GuzzleHttp\Client;
use Spatie\Crawler\JavaScriptRenderers\CloudflareRenderer;

$renderer = new CloudflareRenderer(
    'https://your-worker.your-domain.workers.dev/render',
    new Client(['timeout' => 30]),
);
```

## Custom renderers

You can create your own JavaScript renderer by implementing the `JavaScriptRenderer` interface:

```php
use Spatie\Crawler\JavaScriptRenderers\JavaScriptRenderer;

class MyRenderer implements JavaScriptRenderer
{
    public function getRenderedHtml(string $url): string
    {
        // return the rendered HTML for the given URL
    }
}
```

Then pass it to the crawler:

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->executeJavaScript(new MyRenderer())
    ->start();
```

## Disabling JavaScript execution

If you've enabled JavaScript rendering but want to disable it later in a chain:

```php
use Spatie\Crawler\Crawler;

$crawler = Crawler::create('https://example.com')
    ->executeJavaScript();

$crawler->doNotExecuteJavaScript();
```
