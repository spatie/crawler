# 🕸 Crawl the web using PHP 🕷

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/crawler.svg?style=flat-square)](https://packagist.org/packages/spatie/crawler)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
![Tests](https://github.com/spatie/crawler/workflows/Tests/badge.svg)
![Check & fix styling](https://github.com/spatie/crawler/workflows/Code%20style/badge.svg)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/crawler.svg?style=flat-square)](https://packagist.org/packages/spatie/crawler)

This package provides a class to crawl links on a website. Under the hood Guzzle promises are used to [crawl multiple urls concurrently](http://docs.guzzlephp.org/en/latest/quickstart.html?highlight=pool#concurrent-requests).

Because the crawler can execute JavaScript, it can crawl JavaScript rendered sites. Under the hood [Chrome and Puppeteer](https://github.com/spatie/browsershot) are used to power this feature.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/crawler.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/crawler)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

This package can be installed via Composer:

``` bash
composer require spatie/crawler
```

## Usage

The crawler can be instantiated like this

```php
use Spatie\Crawler\Crawler;

Crawler::create()
    ->setCrawlObserver(<class that extends \Spatie\Crawler\CrawlObservers\CrawlObserver>)
    ->startCrawling($url);
```

The argument passed to `setCrawlObserver` must be an object that extends the `\Spatie\Crawler\CrawlObservers\CrawlObserver` abstract class:

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
    public function finishedCrawling()
    {

    }
}
```

### Using multiple observers

You can set multiple observers with `setCrawlObservers`:

```php
Crawler::create()
    ->setCrawlObservers([
        <class that extends \Spatie\Crawler\CrawlObservers\CrawlObserver>,
        <class that extends \Spatie\Crawler\CrawlObservers\CrawlObserver>,
        ...
     ])
    ->startCrawling($url);
```

Alternatively you can set multiple observers one by one with `addCrawlObserver`:

```php
Crawler::create()
    ->addCrawlObserver(<class that extends \Spatie\Crawler\CrawlObservers\CrawlObserver>)
    ->addCrawlObserver(<class that extends \Spatie\Crawler\CrawlObservers\CrawlObserver>)
    ->addCrawlObserver(<class that extends \Spatie\Crawler\CrawlObservers\CrawlObserver>)
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
This package uses [Puppeteer](https://github.com/puppeteer/puppeteer) under the hood. Here are some pointers on [how to install it on your system](https://github.com/spatie/browsershot#requirements).

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
an object that extends `Spatie\Crawler\CrawlProfiles\CrawlProfile`:

```php
/*
 * Determine if the given url should be crawled.
 */
public function shouldCrawl(UriInterface $url): bool;
```

This package comes with three `CrawlProfiles` out of the box:

- `CrawlAllUrls`: this profile will crawl all urls on all pages including urls to an external site.
- `CrawlInternalUrls`: this profile will only crawl the internal urls on the pages of a host.
- `CrawlSubdomains`: this profile will only crawl the internal urls and its subdomains on the pages of a host.

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

### Accept links with rel="nofollow" attribute

By default, the crawler will reject all links containing attribute rel="nofollow". It is possible to disable these checks like so:

```php
Crawler::create()
    ->acceptNofollowLinks()
    ...
```

### Using a custom User Agent ###

In order to respect robots.txt rules for a custom User Agent you can specify your own custom User Agent.

```php
Crawler::create()
    ->setUserAgent('my-agent')
```

You can add your specific crawl rule group for 'my-agent' in robots.txt. This example disallows crawling the entire site for crawlers identified by 'my-agent'.

```txt
// Disallow crawling for my-agent
User-agent: my-agent
Disallow: /
```

## Setting the number of concurrent requests

To improve the speed of the crawl the package concurrently crawls 10 urls by default. If you want to change that number you can use the `setConcurrency` method.

```php
Crawler::create()
    ->setConcurrency(1) // now all urls will be crawled one by one
```

## Defining Crawl Limits

By default, the crawler continues until it has crawled every page it can find. This behavior might cause issues if you are working in an environment with limitations such as a serverless environment.

The crawl behavior can be controlled with the following two options:

 - **Total Crawl Limit** (`setTotalCrawlLimit`): This limit defines the maximal count of URLs to crawl.
 - **Current Crawl Limit** (`setCurrentCrawlLimit`): This defines how many URLs are processed during the current crawl.

Let's take a look at some examples to clarify the difference between these two methods.

### Example 1: Using the total crawl limit

The `setTotalCrawlLimit` method allows to limit the total number of URLs to crawl, no matter often you call the crawler.

```php
$queue = <your selection/implementation of a queue>;

// Crawls 5 URLs and ends.
Crawler::create()
    ->setCrawlQueue($queue)
    ->setTotalCrawlLimit(5)
    ->startCrawling($url);

// Doesn't crawl further as the total limit is reached.
Crawler::create()
    ->setCrawlQueue($queue)
    ->setTotalCrawlLimit(5)
    ->startCrawling($url);
```

### Example 2: Using the current crawl limit

The `setCurrentCrawlLimit` will set a limit on how many URls will be crawled per execution. This piece of code will process 5 pages with each execution, without a total limit of pages to crawl.

```php
$queue = <your selection/implementation of a queue>;

// Crawls 5 URLs and ends.
Crawler::create()
    ->setCrawlQueue($queue)
    ->setCurrentCrawlLimit(5)
    ->startCrawling($url);

// Crawls the next 5 URLs and ends.
Crawler::create()
    ->setCrawlQueue($queue)
    ->setCurrentCrawlLimit(5)
    ->startCrawling($url);
```

### Example 3: Combining the total and crawl limit

Both limits can be combined to control the crawler:

```php
$queue = <your selection/implementation of a queue>;

// Crawls 5 URLs and ends.
Crawler::create()
    ->setCrawlQueue($queue)
    ->setTotalCrawlLimit(10)
    ->setCurrentCrawlLimit(5)
    ->startCrawling($url);

// Crawls the next 5 URLs and ends.
Crawler::create()
    ->setCrawlQueue($queue)
    ->setTotalCrawlLimit(10)
    ->setCurrentCrawlLimit(5)
    ->startCrawling($url);

// Doesn't crawl further as the total limit is reached.
Crawler::create()
    ->setCrawlQueue($queue)
    ->setTotalCrawlLimit(10)
    ->setCurrentCrawlLimit(5)
    ->startCrawling($url);
```

### Example 4: Crawling across requests

You can use the `setCurrentCrawlLimit` to break up long running crawls. The following example demonstrates a (simplified) approach. It's made up of an initial request and any number of follow-up requests continuing the crawl.

#### Initial Request

To start crawling across different requests, you will need to create a new queue of your selected queue-driver. Start by passing the queue-instance to the crawler. The crawler will start filling the queue as pages are processed and new URLs are discovered. Serialize and store the queue reference after the crawler has finished (using the current crawl limit).

```php
// Create a queue using your queue-driver.
$queue = <your selection/implementation of a queue>;

// Crawl the first set of URLs
Crawler::create()
    ->setCrawlQueue($queue)
    ->setCurrentCrawlLimit(10)
    ->startCrawling($url);

// Serialize and store your queue
$serializedQueue = serialize($queue);
```

#### Subsequent Requests

For any following requests you will need to unserialize your original queue and pass it to the crawler:

```php
// Unserialize queue
$queue = unserialize($serializedQueue);

// Crawls the next set of URLs
Crawler::create()
    ->setCrawlQueue($queue)
    ->setCurrentCrawlLimit(10)
    ->startCrawling($url);

// Serialize and store your queue
$serialized_queue = serialize($queue);
```

The behavior is based on the information in the queue. Only if the same queue-instance is passed in the behavior works as described. When a completely new queue is passed in, the limits of previous crawls -even for the same website- won't apply.

An example with more details can be found [here](https://github.com/spekulatius/spatie-crawler-cached-queue-example).

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

## Add a delay between requests

In some cases you might get rate-limited when crawling too aggressively. To circumvent this, you can use the `setDelayBetweenRequests()` method to add a pause between every request. This value is expressed in milliseconds.

```php
Crawler::create()
    ->setDelayBetweenRequests(150) // After every page crawled, the crawler will wait for 150ms
```

## Limiting which content-types to parse

By default, every found page will be downloaded (up to `setMaximumResponseSize()` in size) and parsed for additional links. You can limit which content-types should be downloaded and parsed by setting the `setParseableMimeTypes()` with an array of allowed types.

```php
Crawler::create()
    ->setParseableMimeTypes(['text/html', 'text/plain'])
```

This will prevent downloading the body of pages that have different mime types, like binary files, audio/video, ... that are unlikely to have links embedded in them. This feature mostly saves bandwidth.

## Using a custom crawl queue

When crawling a site the crawler will put urls to be crawled in a queue. By default, this queue is stored in memory using the built-in `ArrayCrawlQueue`.

When a site is very large you may want to store that queue elsewhere, maybe a database. In such cases, you can write your own crawl queue.

A valid crawl queue is any class that implements the `Spatie\Crawler\CrawlQueues\CrawlQueue`-interface. You can pass your custom crawl queue via the `setCrawlQueue` method on the crawler.

```php
Crawler::create()
    ->setCrawlQueue(<implementation of \Spatie\Crawler\CrawlQueues\CrawlQueue>)
```

Here

- [ArrayCrawlQueue](https://github.com/spatie/crawler/blob/master/src/CrawlQueues/ArrayCrawlQueue.php)
- [RedisCrawlQueue (third-party package)](https://github.com/repat/spatie-crawler-redis)
- [CacheCrawlQueue for Laravel (third-party package)](https://github.com/spekulatius/spatie-crawler-toolkit-for-laravel)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Testing

First, install the Puppeteer dependency, or your tests will fail.

```
npm install puppeteer
```

To run the tests you'll have to start the included node based server first in a separate terminal window.

```bash
cd tests/server
npm install
node server.js
```

With the server running, you can start testing.
```bash
composer tests
```

## Security

If you discover any security related issues, please email freek@spatie.be instead of using the issue tracker.

## Postcardware

You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using.

Our address is: Spatie, Kruikstraat 22, 2018 Antwerp, Belgium.

We publish all received postcards [on our company website](https://spatie.be/en/opensource/postcards).

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
