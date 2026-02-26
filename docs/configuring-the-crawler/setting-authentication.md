---
title: Setting authentication
weight: 12
---

When crawling sites that require authentication, you can use the `basicAuth` or `token` methods.

## Basic authentication

The `basicAuth` method configures HTTP Basic authentication for all requests.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->basicAuth('username', 'password')
    ->start();
```

## Bearer tokens

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
