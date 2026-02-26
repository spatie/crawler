---
title: Respecting robots.txt
weight: 5
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

You can re-enable robots checking after disabling it using the `respectRobots` method.

```php
$crawler = Crawler::create('https://example.com')
    ->ignoreRobots();

// later...
$crawler->respectRobots();
```

## Accepting nofollow links

By default, the crawler will reject all links containing `rel="nofollow"`. You can disable this check using the `followNofollow` method.

```php
use Spatie\Crawler\Crawler;

Crawler::create('https://example.com')
    ->followNofollow()
    ->start();
```

You can re-enable nofollow rejection using the `rejectNofollowLinks` method.

```php
$crawler = Crawler::create('https://example.com')
    ->followNofollow();

// later...
$crawler->rejectNofollowLinks();
```

## Custom user agent

The [user agent](/docs/crawler/v9/configuring-the-crawler/configuring-requests#user-agent) is also used when checking robots.txt rules. When you set a custom user agent, robots.txt rules specific to that agent will be respected. For example, if your robots.txt contains:

```txt
User-agent: my-agent
Disallow: /
```

The crawler (when using `my-agent` as user agent) will not crawl any pages on the site.
