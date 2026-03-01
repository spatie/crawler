<div align="left">
    <a href="https://spatie.be/open-source?utm_source=github&utm_medium=banner&utm_campaign=crawler">
      <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://spatie.be/packages/header/crawler/html/dark.webp?">
        <img alt="Logo for crawler" src="https://spatie.be/packages/header/crawler/html/light.webp">
      </picture>
    </a>

<h1>Crawl the web using PHP</h1>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/crawler.svg?style=flat-square)](https://packagist.org/packages/spatie/crawler)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
![Tests](https://github.com/spatie/crawler/workflows/Tests/badge.svg)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/crawler.svg?style=flat-square)](https://packagist.org/packages/spatie/crawler)

</div>

This package provides a powerful, easy to use class to crawl links on a website. Under the hood, Guzzle promises are used to [crawl multiple URLs concurrently](http://docs.guzzlephp.org/en/latest/quickstart.html?highlight=pool#concurrent-requests).

Because the crawler can execute JavaScript, it can crawl JavaScript rendered sites. Under the hood, [Chrome and Puppeteer](https://github.com/spatie/browsershot) are used to power this feature.

Here's a quick example:

```php
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlResponse;

Crawler::create('https://example.com')
    ->onCrawled(function (string $url, CrawlResponse $response) {
        echo "{$url}: {$response->status()}\n";
    })
    ->start();
```

Or collect all URLs on a site:

```php
$urls = Crawler::create('https://example.com')
    ->internalOnly()
    ->depth(3)
    ->foundUrls();
```

You can also test your crawl logic without making real HTTP requests:

```php
Crawler::create('https://example.com')
    ->fake([
        'https://example.com' => '<html><a href="/about">About</a></html>',
        'https://example.com/about' => '<html>About page</html>',
    ])
    ->foundUrls();
```

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/crawler.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/crawler)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Documentation

All documentation is available [on our documentation site](https://spatie.be/docs/crawler).

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
