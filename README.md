# Crawl links on a website

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/crawler.svg?style=flat-square)](https://packagist.org/packages/spatie/crawler)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/spatie/crawler/master.svg?style=flat-square)](https://travis-ci.org/spatie/crawler)
[![Quality Score](https://img.shields.io/scrutinizer/g/spatie/crawler.svg?style=flat-square)](https://scrutinizer-ci.com/g/spatie/crawler)
[![StyleCI](https://styleci.io/repos/45406338/shield)](https://styleci.io/repos/45406338)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/crawler.svg?style=flat-square)](https://packagist.org/packages/spatie/crawler)

This package provides a class to crawl links on a website. Under the hood Guzzle promises are used to [crawl multiple urls concurrently](http://docs.guzzlephp.org/en/latest/quickstart.html?highlight=pool#concurrent-requests).

Spatie is a webdesign agency in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

## Postcardware

You're free to use this package (it's [MIT-licensed](LICENSE.md)), but if it makes it to your production environment we highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using.

Our address is: Spatie, Samberstraat 69D, 2060 Antwerp, Belgium.

All postcards are published [on our website](https://spatie.be/en/opensource/postcards).

## Installation

This package can be installed via Composer:

``` bash
composer require spatie/crawler
```

## Usage

The crawler can be instantiated like this

```php
Crawler::create()
    ->setCrawlObserver(<implementation of \Spatie\Crawler\CrawlObserver>)
    ->startCrawling($url);
```

The argument passed to `setCrawlObserver` must be an object that implements the `\Spatie\Crawler\CrawlObserver` interface:

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
 * @param \Spatie\Crawler\Url $url
 * @param \Psr\Http\Message\ResponseInterface $response
 * @param \Spatie\Crawler\Url $foundOn
 */
public function hasBeenCrawled(Url $url, ResponseInterface $response, Url $foundOn);

/**
 * Called when the crawl has ended.
 */
public function finishedCrawling();
``` 

### Filtering certain urls

You can tell the crawler not to visit certain urls by passing using the `setCrawlProfile`-function. That function expects
an objects that implements the `Spatie\Crawler\CrawlProfile`-interface:

```php
/*
 * Determine if the given url should be crawled.
 */
public function shouldCrawl(Url $url): bool;
```

This package comes with two `CrawlProfiles` out of the box:

- `CrawlAllUrls`: this profile will crawl all urls on all pages including urls to an external site. 
- `CrawlInternalUrls`: this profile will only crawl the internal urls on the pages of a host.

## Setting the number of concurrent requests

To improve the speed of the crawl the package concurrently crawls 10 urls by default. If you want to change that number you can use the `setConcurrency` method.

```php
Crawler::create()
    ->setCrawlObserver(<implementation of \Spatie\Crawler\CrawlObserver>)
    ->setConcurrency(1) //now all urls will be crawled one by one
    ->startCrawling($url);
```


## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Testing

To run the tests you'll have to start the included node based server first in a separate terminal window.

```bash
cd tests/server
./start_server.sh
```

With the server running, you can start testing.
```bash
vendor/bin/phpunit
```

## Security

If you discover any security related issues, please email freek@spatie.be instead of using the issue tracker.

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## About Spatie
Spatie is a webdesign agency in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
