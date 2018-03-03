# Upgrading

Because there are many breaking changes an upgrade is not that easy. 
There are many edge cases this guide does not cover. 
We accept PRs to improve this guide.

## From v3 to v4

- The `CrawlObserver` and `CrawlProfile` are upgraded from interfaces to abstract classes, so you have to convert your old observers and profiles. `crawled` now receives every successfully crawled uri, `crawlFailed` every failed one.

## From v2 to v3

- PHP 7.1 is now required as a minimum version.
- Instead of using our custom `\Spatie\Crawler\Url` object, we're now using the `Psr\Http\Message\UriInterface`. 
Custom Profiles and Observers will need to be changed to have the correct arguments and return types.
We're using `\GuzzleHttp\Psr7\Uri` as the concrete URI implementation.
