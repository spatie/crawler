# Crawl links on a website

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/crawler.svg?style=flat-square)](https://packagist.org/packages/spatie/crawler)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/spatie/crawler/master.svg?style=flat-square)](https://travis-ci.org/spatie/crawler)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/048cebe6-0463-4302-8317-0af7cc48e81c.svg?style=flat-square)](https://insight.sensiolabs.com/projects/048cebe6-0463-4302-8317-0af7cc48e81c)
[![Quality Score](https://img.shields.io/scrutinizer/g/spatie/crawler.svg?style=flat-square)](https://scrutinizer-ci.com/g/spatie/crawler)
[![StyleCI](https://styleci.io/repos/45406338/shield)](https://styleci.io/repos/45406338)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/crawler.svg?style=flat-square)](https://packagist.org/packages/spatie/crawler)

This package provides a class to crawl links on a website.

Spatie is a webdesign agency in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

## Postcardware

You're free to use this package (it's [MIT-licensed](LICENSE.md)), but if it makes it to your production environment you are required to send us a postcard from your hometown, mentioning which of our package(s) you are using.

Our address is: Spatie, Samberstraat 69D, 2060 Antwerp, Belgium.

The best postcards will get published on the open source page on our website.

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
 * @param \Spatie\Crawler\Url       $url
 * @param \Psr\Http\Message\ResponseInterface $response
 */
public function hasBeenCrawled(Url $url, ResponseInterface $response);

/**
 * Called when the crawl has ended.
 */
public function finishedCrawling();
``` 

### Filtering certain url's

You can instruct the crawler not to visit certain url's by using the `setCrawlProfile` method. It expects
an object that implements the `Spatie\Crawler\CrawlProfile` interface:

```php
/**
 * Set the crawl profile.
 *
 * @param \Spatie\Crawler\CrawlProfile $crawlProfile
 *
 * @return $this
*/
public function setCrawlProfile(CrawlProfile $crawlProfile)
{
    $this->crawlProfile = $crawlProfile;
    return $this;
}

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
