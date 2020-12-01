# Changelog

All notable changes to `spatie/crawler` will be documented in this file.

## 4.7.6 - 2020-12-30

- add support for PHP 8

## 4.7.5 - 2020-09-12

- treat connection exceptions as request exceptions

## 4.7.4 - 2020-07-15

- fix: method and property name error (#311)

## 4.7.3 - 2020-07-15

- add crawler option to allow crawl links with rel="nofollow" (#310)

## 4.7.2 - 2020-05-06

- only crawl links that are completely parsed

## 4.7.1 - 2020-04-14

- fix curl streaming responses (#295)

## 4.7.0 - 2020-04-14

- add `setParseableMimeTypes()` (#293)

## 4.6.9 - 2020-04-11

- fix LinkAdder not receiving the updated DOM (#292)

## 4.6.8 - 2020-03-12

- allow tightenco/collect 7 (#282)

## 4.6.7 - 2020-03-09

- respect maximum response size when checking Robots Meta tags (#281)

## 4.6.6 - 2020-01-30

- allow Guzzle 7

## 4.6.5 - 2019-11-23

- allow symfony 5 components

## 4.6.4 - 2019-09-10

- allow tightenco/collect 6.0 and up (#261)

## 4.6.3 - 2019-09-09

- fix crash when `CrawlRequestFailed` receives an exception other than `RequestException`

## 4.6.2 - 2019-08-08

- case-insensitive user agent bugfix (#249)

## 4.6.1 - 2019-08-08

- fix bugs in `hasAlreadyBeenProcessed`

## 4.6.0 - 2019-08-07

**THIS VERSION CONTAINS A CRITICAL BUG, DO NOT USE**

- added `ArrayCrawlQueue`; this is now the default queue
- deprecated `CollectionCrawlQueue`

## 4.5.0 - 2019-07-22

- Make user agent configurable (#246)

## 4.4.3 - 2019-06-22

- `delayBetweenRequests` now uses `int` instead of `float` everywhere

## 4.4.2 - 2019-06-20

- remove incorrect docblock

## 4.4.1 - 2019-06-06

- handle relative paths after redirects correctly

## 4.4.0 - 2019-04-05

- add `getUrls` and `getPendingUrls`

## 4.3.2 - 2019-04-04

- Respect maximumDepth in combination with robots (#181) 

## 4.3.1 - 2019-04-03

- Properly handle `noindex,follow` urls.

## 4.3.0 - 2019-03-11

- added capability of crawling links with rel= next or prev

## 4.2.0 - 2018-10-31

- add `setDelayBetweenRequests`

## 4.1.7 - 2018-07-27

- fix an issue where the node in the depthtree could be null

## 4.1.6 - 2018-06-26

- improve performance by only building the depth three when needed
- handlers will get html after JavaScript has been processed

## 4.1.5 - 2018-06-25

- refactor to improve extendability

## 4.1.4 - 2018-06-09

- always add links to pool if robots shouldn't be respected

## 4.1.3 - 2018-06-05

- refactor of internals

## 4.1.2 - 2018-06-03

- make it possible to override `$defaultClientOptions`

## 4.1.1 - 2018-05-22

- Bump minimum required version of `spatie/robots-txt` to `1.0.1`.

## 4.1.0 - 2018-05-08

- Respect robots.txt

## 4.0.5 - 2018-04-30

- improved extensibility by removing php native type hinting of url, queue and crawler pool Closures

## 4.0.4 - 2018-03-20

- do not follow links that have attribute `rel` set to `nofollow`

## 4.0.3 - 2018-03-02

- Support both `Illuminate`'s and `Tighten`'s `Collection`. 

## 4.0.2 - 2018-03-01

- fix bugs when installing into a Laravel app

## 4.0.0 - 2018-03-01

- the `CrawlObserver` and `CrawlProfile` are upgraded from interfaces to abstract classes
- don't crawl `tel:` links

## 3.2.1 - 2018-02-21
- fix endless loop

## 3.2.0 - 2018-01-25
- add `setCrawlObservers`, `addCrawlObserver`

## 3.1.3 - 2018-01-19
- fix `setMaximumResponseSize` (someday we'll get this right)

## 3.1.2 - 2018-01-19
**CONTAINS BUGS, DO NOT USE THIS VERSION**

- fix `setMaximumResponseSize`

## 3.1.1 - 2018-01-17
**CONTAINS BUGS, DO NOT USE THIS VERSION**

- fix `setMaximumResponseSize`

## 3.1.0 - 2018-01-11
**CONTAINS BUGS, DO NOT USE THIS VERSION**

- add `setMaximumResponseSize`

## 3.0.1 - 2018-01-02
- fix for exception being thrown when encountering a malformatted url

## 3.0.0 - 2017-12-22
- use `\Psr\Http\Message\UriInterface` for all urls
- use Puppeteer
- drop support from PHP 7.0

## 2.7.1 - 2017-12-13
- allow symfony 4 crawler

## 2.7.0 - 2017-12-10
- added the ability to change the crawl queue

## 2.6.2 - 2017-12-10
- more performance improvements

## 2.6.1 - 2017-12-10
- performance improvements

## 2.6.0 - 2017-10-16
- add `CrawlSubdomains` profile

## 2.5.0 - 2017-09-27
- add crawl count limit

## 2.4.0 - 2017-09-21
- add depth limit

## 2.3.0 - 2017-09-21
- add JavaScript execution

## 2.2.1 - 2017-09-07
- fix deps for PHP 7.2

## 2.2.0 - 2017-08-03
- add `EmptyCrawlObserver`

## 2.1.2 - 2017-03-06
- refactor to make use of Symfony Crawler's `link` function

## 2.1.1 - 2017-03-03
- fix bugs around relative urls

## 2.1.0 - 2017-01-27
- add `CrawlInternalUrls`

## 2.0.7 - 2016-12-30
- make sure the passed client options are being used

## 2.0.6 - 2016-12-15
- second attempt to fix detection of redirects

## 2.0.5 - 2016-12-15
- fix detection of redirects

## 2.0.4 - 2016-12-15
- fix the default timeout of 5 seconds

## 2.0.3 - 2016-12-13
- set a default timeout of 5 seconds

## 2.0.2 - 2016-12-05
- fix for non responding hosts

## 2.0.1 - 2016-12-05
- fix for the accidental crawling of mailto-links

## 2.0.0 - 2016-12-05
- improve performance by concurrent crawling
- make it possible to determine on which url a url was found

## 1.3.1 - 2015-09-13
- Ignore `tel:` links when crawling

## 1.3.0 - 2015-08-18
- Added `path`, `segment` and `segments` functions to `Url`

## 1.2.3 - 2015-08-12
- Updated the required version of Guzzle to a secure version

## 1.2.2 - 2015-03-08
- Fixed a bug where the crawler would not take query strings into account

## 1.2.1 - 2015-03-01
- Fixed a bug where the crawler tries to follow JavaScript links

## 1.2.0 - 2015-12-21
- Add support for DomCrawler 3.x

## 1.1.1 - 2015-11-16
- Fix for normalizing relative links when using non-80 ports

## 1.1.0 - 2015-11-16
- Add support for custom ports

## 1.0.2 - 2015-11-05
- Lower required php version to 5.5

## 1.0.1 - 2015-11-03
- Make url's case sensitive

## 1.0.0 - 2015-11-03
- First release
