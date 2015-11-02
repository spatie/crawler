# Crawl all links found on a website

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/crawler.svg?style=flat-square)](https://packagist.org/packages/spatie/crawler)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/spatie/crawler/master.svg?style=flat-square)](https://travis-ci.org/spatie/crawler)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/048cebe6-0463-4302-8317-0af7cc48e81c.svg?style=flat-square)](https://insight.sensiolabs.com/projects/048cebe6-0463-4302-8317-0af7cc48e81c)
[![Quality Score](https://img.shields.io/scrutinizer/g/spatie/crawler.svg?style=flat-square)](https://scrutinizer-ci.com/g/spatie/crawler)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/crawler.svg?style=flat-square)](https://packagist.org/packages/spatie/crawler)

This package provides a class to crawl all links on a website.

## Installation

This package can be installed via Composer:

``` bash
composer require spatie/crawler
```

## Usage

The crawler can be instantiated like this

```php
Crawler::create()
    ->setObserver(<implementation of \Spatie\Crawler\CrawlObserver)
    ->startCrawling($url);
```

The argument passed to `setObserver` must be an instance that implement the `\Spatie\Crawler\CrawlObserver`-interface:

```php
/**
 * Called when the crawler will crawl the given url.
 *
 * @param \Spatie\Crawler\Url $url
 */
public function willCrawl(Url $url);

/**
 * Called when the crawler has crawled the given url.
 *
 * @param \Spatie\Crawler\Url       $url
 * @param \Psr\Http\Message\ResponseInterface $response
 */
public function haveCrawled(Url $url, ResponseInterface $response);

/**
 * Called when the crawl has ended.
 */
public function finishedCrawling();
``` 

### Filtering out certain Url's

You can tell the crawler not visit certain url's by passing using the `setCrawlProfile`-function. That function expects
an objects that implements the `Spatie\Crawler\CrawlProfile`-interface:

```php
/**
 * Determine if the given url should be crawled.
 *
 * @param \Spatie\Crawler\Url $url
 *
 * @return bool
 */
public function shouldCrawl(Url $url);
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email freek@spatie.be instead of using the issue tracker.

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## About Spatie
Spatie is a webdesign agency in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
