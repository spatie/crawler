---
title: Configuring requests
weight: 3
---

## User agent

By default, the crawler identifies itself as `*`. You can set a custom user agent using the `userAgent` method.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->userAgent('MyBot/1.0')
    ->start();
```

The user agent is also used when checking `robots.txt` rules, so make sure it matches any user agent specific rules you want to respect.

## Extra headers

You can add extra headers to every request the crawler makes using the `headers` method.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->headers([
        'Accept-Language' => 'en-US',
        'X-Custom-Header' => 'value',
    ])
    ->start();
```

The headers will be merged with the default headers. You can call `headers` multiple times. Each call will merge the new headers with the previously set ones.

## Timeouts

By default, the crawler uses a 10 second timeout for both connecting and receiving a response. You can change these values using the `connectTimeout` and `requestTimeout` methods. Both accept a value in seconds.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->connectTimeout(5)
    ->requestTimeout(30)
    ->start();
```

The `connectTimeout` method sets the maximum number of seconds to wait while trying to connect to the server. The `requestTimeout` method sets the maximum number of seconds to wait for the entire request (including the response) to complete.

## Authentication

When crawling sites that require authentication, you can use the `basicAuth` or `token` methods.

The `basicAuth` method configures HTTP Basic authentication for all requests.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->basicAuth('username', 'password')
    ->start();
```

The `token` method sets an `Authorization` header. It defaults to the `Bearer` type.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->token('your-api-token')
    ->start();
```

You can pass a second argument to change the token type.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->token('your-api-token', 'Token')
    ->start();
```

## SSL verification

When crawling sites with self-signed or invalid SSL certificates (for example, a staging environment), you can disable certificate verification using the `withoutVerifying` method.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://staging.example.com')
    ->withoutVerifying()
    ->start();
```

You should only use this for trusted environments. In production, always keep SSL verification enabled.

## Proxy

You can route all crawler requests through a proxy server using the `proxy` method.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->proxy('http://proxy-server:8080')
    ->start();
```

This accepts any proxy string supported by Guzzle, including authenticated proxies.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->proxy('http://username:password@proxy-server:8080')
    ->start();
```

## Cookies

You can send cookies with every request using the `cookies` method. This is useful when you need to crawl a site that requires a session cookie or other cookie based authentication.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->cookies(['session_id' => 'abc123', 'token' => 'xyz'], 'example.com')
    ->start();
```

The first argument is an array of cookie names and values. The second argument is the domain the cookies belong to.

## Query parameters

You can append query parameters to every request the crawler makes using the `queryParameters` method. This is useful for passing API keys or other parameters that need to be present on every request.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->queryParameters(['api_key' => 'your-key'])
    ->start();
```

You can call `queryParameters` multiple times. Each call will merge the new parameters with the previously set ones.

## Retrying failed requests

Some servers occasionally return 5xx errors or drop connections. You can configure the crawler to automatically retry failed requests using the `retry` method.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->retry(times: 2, delayInMs: 500)
    ->start();
```

The first argument is the maximum number of retries per request. The second argument is the base delay between retries in milliseconds. The delay increases linearly with each attempt (500ms, 1000ms, 1500ms, ...).

A request will be retried when it results in a connection error or a 5xx response status code.

## Guzzle middleware

You can add custom [Guzzle middleware](https://docs.guzzlephp.org/en/stable/handlers-and-middleware.html) to the underlying HTTP client using the `middleware` method. This lets you hook into the request/response lifecycle for logging, caching, modifying headers, or any other purpose.

```php
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->middleware(Middleware::mapRequest(function (RequestInterface $request) {
        return $request->withHeader('X-Custom-Header', 'value');
    }), 'add-custom-header')
    ->start();
```

The first argument is a callable that follows Guzzle's middleware signature. The optional second argument is a name for the middleware, which can be useful for debugging.

You can call `middleware` multiple times to add multiple middlewares. They will be pushed onto the handler stack in the order they are added.

```php
use GuzzleHttp\Middleware;
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->middleware($loggingMiddleware, 'logging')
    ->middleware($cachingMiddleware, 'caching')
    ->start();
```
