# 🕸 Easily crawl the web using PHP 🕷

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/crawler.svg?style=flat-square)](https://packagist.org/packages/spatie/crawler)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/spatie/crawler/master.svg?style=flat-square)](https://travis-ci.org/spatie/crawler)
[![StyleCI](https://styleci.io/repos/45406338/shield)](https://styleci.io/repos/45406338)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/crawler.svg?style=flat-square)](https://packagist.org/packages/spatie/crawler)

This package provides a class to crawl links on a website. Under the hood Guzzle promises are used to [crawl multiple urls concurrently](http://docs.guzzlephp.org/en/latest/quickstart.html?highlight=pool#concurrent-requests).

Because the crawler can execute JavaScript, it can crawl JavaScript rendered sites. Under the hood [Chrome and Puppeteer](https://github.com/spatie/browsershot) are used to power this feature.

Spatie is a webdesign agency in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

## Installation

This package can be installed via Composer:

``` bash
composer require spatie/crawler
```

## Usage

The crawler can be instantiated like this

```php
Crawler::create()
    ->setCrawlObserver(<class that extends \Spatie\Crawler\CrawlObserver>)
    ->startCrawling($url);
```

The argument passed to `setCrawlObserver` must be an object that extends the `\Spatie\Crawler\CrawlObserver` abstract class:

```php
namespace Spatie\Crawler;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

abstract class CrawlObserver
{
    /**
     * Called when the crawler will crawl the url.
     *
     * @param \Psr\Http\Message\UriInterface $url
     */
    public function willCrawl(UriInterface $url)
    {

    }

    /**
     * Called when the crawler has crawled the given url successfully.
     *
     * @param \Psr\Http\Message\UriInterface $url
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param \Psr\Http\Message\UriInterface|null $foundOnUrl
     */
    abstract public function crawled(
        UriInterface $url,
        ResponseInterface $response,
        ?UriInterface $foundOnUrl = null
    );

    /**
     * Called when the crawler had a problem crawling the given url.
     *
     * @param \Psr\Http\Message\UriInterface $url
     * @param \GuzzleHttp\Exception\RequestException $requestException
     * @param \Psr\Http\Message\UriInterface|null $foundOnUrl
     */
    abstract public function crawlFailed(
        UriInterface $url,
        RequestException $requestException,
        ?UriInterface $foundOnUrl = null
    );

    /**
     * Called when the crawl has ended.
     */
    public function finishedCrawling() {

    }
}
```

### Using multiple observers

You can set multiple observers with `setCrawlObservers`:

```php
Crawler::create()
    ->setCrawlObservers([
        <class that extends \Spatie\Crawler\CrawlObserver>,
        <class that extends \Spatie\Crawler\CrawlObserver>,
        ...
     ])
    ->startCrawling($url);
```

Alternatively you can set multiple observers one by one with `addCrawlObserver`:

```php
Crawler::create()
    ->addCrawlObserver(<class that extends \Spatie\Crawler\CrawlObserver>)
    ->addCrawlObserver(<class that extends \Spatie\Crawler\CrawlObserver>)
    ->addCrawlObserver(<class that extends \Spatie\Crawler\CrawlObserver>)
    ->startCrawling($url);
```

### Executing JavaScript

By default, the crawler will not execute JavaScript. This is how you can enable the execution of JavaScript:

```php
Crawler::create()
    ->executeJavaScript()
    ...
```

In order to make it possible to get the body html after the javascript has been executed, this package depends on
our [Browsershot](https://github.com/spatie/browsershot) package.
This package uses [Puppeteer](https://github.com/GoogleChrome/puppeteer) under the hood. Here are some pointers on [how to install it on your system](https://github.com/spatie/browsershot#requirements).

Browsershot will make an educated guess as to where its dependencies are installed on your system.
By default, the Crawler will instantiate a new Browsershot instance. You may find the need to set a custom created instance using the `setBrowsershot(Browsershot $browsershot)` method.

```php
Crawler::create()
    ->setBrowsershot($browsershot)
    ->executeJavaScript()
    ...
```

Note that the crawler will still work even if you don't have the system dependencies required by Browsershot.
These system dependencies are only required if you're calling `executeJavaScript()`.

### Filtering certain urls

You can tell the crawler not to visit certain urls by using the `setCrawlProfile`-function. That function expects
an object that extends `Spatie\Crawler\CrawlProfile`:

```php
/*
 * Determine if the given url should be crawled.
 */
public function shouldCrawl(UriInterface $url): bool;
```

This package comes with three `CrawlProfiles` out of the box:

- `CrawlAllUrls`: this profile will crawl all urls on all pages including urls to an external site.
- `CrawlInternalUrls`: this profile will only crawl the internal urls on the pages of a host.
- `CrawlSubdomainUrls`: this profile will only crawl the internal urls and its subdomains on the pages of a host.

### Ignoring robots.txt and robots meta

By default, the crawler will respect robots data. It is possible to disable these checks like so:

```php
Crawler::create()
    ->ignoreRobots()
    ...
```

Robots data can come from either a `robots.txt` file, meta tags or response headers.
More information on the spec can be found here: [http://www.robotstxt.org/](http://www.robotstxt.org/).

Parsing robots data is done by our package [spatie/robots-txt](https://github.com/spatie/robots-txt).

## Setting the number of concurrent requests

To improve the speed of the crawl the package concurrently crawls 10 urls by default. If you want to change that number you can use the `setConcurrency` method.

```php
Crawler::create()
    ->setConcurrency(1) //now all urls will be crawled one by one
```

## Setting the maximum crawl count

By default, the crawler continues until it has crawled every page of the supplied URL. If you want to limit the amount of urls the crawler should crawl you can use the `setMaximumCrawlCount` method.

```php
// stop crawling after 5 urls

Crawler::create()
    ->setMaximumCrawlCount(5)
```

## Setting the maximum crawl depth

By default, the crawler continues until it has crawled every page of the supplied URL. If you want to limit the depth of the crawler you can use the `setMaximumDepth` method.

```php
Crawler::create()
    ->setMaximumDepth(2)
```

## Setting the maximum response size

Most html pages are quite small. But the crawler could accidentally pick up on large files such as PDFs and MP3s. To keep memory usage low in such cases the crawler will only use the responses that are smaller than 2 MB. If, when streaming a response, it becomes larger than 2 MB, the crawler will stop streaming the response. An empty response body will be assumed.

You can change the maximum response size.

```php
// let's use a 3 MB maximum.
Crawler::create()
    ->setMaximumResponseSize(1024 * 1024 * 3)
```

## Using a custom crawl queue

When crawling a site the crawler will put urls to be crawled in a queue. By default, this queue is stored in memory using the built-in `CollectionCrawlQueue`.

When a site is very large you may want to store that queue elsewhere, maybe a database. In such cases, you can write your own crawl queue.

A valid crawl queue is any class that implements the `Spatie\Crawler\CrawlQueue\CrawlQueue`-interface. You can pass your custom crawl queue via the `setCrawlQueue` method on the crawler.

```php
Crawler::create()
    ->setCrawlQueue(<implementation of \Spatie\Crawler\CrawlQueue\CrawlQueue>)
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Testing

To run the tests you'll have to start the included node based server first in a separate terminal window.

```bash
cd tests/server
npm install
./start_server.sh
```

With the server running, you can start testing.
```bash
vendor/bin/phpunit
```

## Security

If you discover any security related issues, please email freek@spatie.be instead of using the issue tracker.

## Postcardware

You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using.

Our address is: Spatie, Samberstraat 69D, 2060 Antwerp, Belgium.

We publish all received postcards [on our company website](https://spatie.be/en/opensource/postcards).

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## Support us

Spatie is a webdesign agency based in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

Does your business depend on our contributions? Reach out and support us on [Patreon](https://www.patreon.com/spatie).
All pledges will be dedicated to allocating workforce on maintenance and new awesome stuff.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
