---
title: Robots
weight: 3
---

By default, the crawler will respect robots data from `robots.txt` files, meta tags, and response headers. More information on the spec can be found at [robotstxt.org](http://www.robotstxt.org/).

Parsing robots data is done by the [spatie/robots-txt](https://github.com/spatie/robots-txt) package.

## Ignoring robots rules

You can disable all robots checks using the `ignoreRobots` method.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->ignoreRobots()
    ->start();
```

## Accepting nofollow links

By default, the crawler will reject all links containing `rel="nofollow"`. You can disable this check using the `acceptNofollowLinks` method.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->acceptNofollowLinks()
    ->start();
```

## Custom user agent

You can specify a custom User Agent using the `userAgent` method.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->userAgent('my-agent')
    ->start();
```

When you set a custom User Agent, robots.txt rules specific to that agent will be respected. For example, if your robots.txt contains:

```txt
User-agent: my-agent
Disallow: /
```

The crawler (when using `my-agent` as User Agent) will not crawl any pages on the site.
