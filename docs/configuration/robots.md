---
title: Robots
weight: 8
---

By default, the crawler will respect robots data from `robots.txt` files, meta tags, and response headers. More information on the spec can be found at [robotstxt.org](http://www.robotstxt.org/).

Parsing robots data is done by the [spatie/robots-txt](https://github.com/spatie/robots-txt) package.

## Ignoring robots rules

You can disable all robots checks:

```php
Crawler::create('https://example.com')
    ->ignoreRobots()
    ->start();
```

## Accepting nofollow links

By default, the crawler will reject all links containing `rel="nofollow"`. You can disable this check:

```php
Crawler::create('https://example.com')
    ->acceptNofollowLinks()
    ->start();
```
