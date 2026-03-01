# Changelog

All notable changes to `spatie/crawler` will be documented in this file.

## 9.0.0 - 2026-03-01

Major rewrite. See [UPGRADING.md](UPGRADING.md) for a full list of breaking changes.

### Changed
- Replace `UriInterface` with plain `string` URLs throughout the API
- Replace `ResponseInterface` with `CrawlResponse` in observer callbacks
- `CrawlProfile` is now an interface instead of an abstract class
- `CrawlObserverCollection` no longer implements `ArrayAccess` or `Iterator`
- Default scheme changed from `http` to `https`
- JavaScript rendering is now driver-based (Browsershot moved to `suggest`)
- `UrlParser` interface redesigned to return `ExtractedUrl[]` instead of adding to queue directly
- `CrawlQueue::has()` now accepts `string` instead of `CrawlUrl|UriInterface`
- `start()` now returns a `FinishReason` enum
- URL is now required in `Crawler::create()`

### Added
- `CrawlResponse` object with `status()`, `body()`, `dom()`, `header()`, `transferStats()`, and more
- `CrawlProgress` tracking with `urlsCrawled`, `urlsFailed`, `urlsFound`, `urlsPending`
- `FinishReason` enum: `Completed`, `CrawlLimitReached`, `TimeLimitReached`, `Interrupted`
- Closure callbacks: `onCrawled()`, `onFailed()`, `onFinished()`, `onWillCrawl()`
- `foundUrls()` to collect all URLs as `CrawledUrl` objects
- `fake()` for testing without HTTP requests
- Scope helpers: `internalOnly()`, `includeSubdomains()`, `shouldCrawl()`
- Shorter method names: `depth()`, `concurrency()`, `delay()`, `limit()`, `userAgent()`
- Throttling: `FixedDelayThrottle` and `AdaptiveThrottle`
- Resource type extraction: `alsoExtract()`, `extractAll()`, `ResourceType` enum
- URL normalization in `ArrayCrawlQueue`
- Graceful shutdown via SIGINT/SIGTERM
- `alwaysCrawl()` and `neverCrawl()` pattern overrides
- `retry()` for automatic retries on connection errors and 5xx responses
- `TransferStatistics` with typed timing accessors
- `CloudflareRenderer` for JavaScript rendering
- `JavaScriptRenderer` interface for custom renderers
- Request configuration: `basicAuth()`, `token()`, `withoutVerifying()`, `proxy()`, `cookies()`, `queryParameters()`, `middleware()`

### Removed
- `Spatie\Crawler\Url` class
- `ResponseWithCachedBody` (replaced by `CrawlResponse`)
- `nicmart/tree` dependency
- `spatie/browsershot` as a required dependency (moved to `suggest`)
- `setBrowsershot()` and `getBrowsershot()` methods
- `startCrawling()` method (use `start()`)
- `setUrlParserClass()` (use `parseSitemaps()` or pass a `UrlParser` directly)

## 8.5.0 - 2026-02-21

Add Laravel 13 support

## 8.4.7 - 2025-11-26

### What's Changed

* Update nicmart/tree dependency version to ^0.10 by @robinmiau in https://github.com/spatie/crawler/pull/497

### New Contributors

* @robinmiau made their first contribution in https://github.com/spatie/crawler/pull/497

**Full Changelog**: https://github.com/spatie/crawler/compare/8.4.6...8.4.7

## 8.4.5 - 2025-10-28

### What's Changed

* When fetching robots.txt, use the same User-Agent as defined by the user by @mattiasgeniar in https://github.com/spatie/crawler/pull/491

**Full Changelog**: https://github.com/spatie/crawler/compare/8.4.4...8.4.5

## 8.4.4 - 2025-10-22

### What's Changed

* Update issue template by @AlexVanderbist in https://github.com/spatie/crawler/pull/488
* fix(CrawlUrl): Use before initialization is now impossible by @Voltra in https://github.com/spatie/crawler/pull/490

### New Contributors

* @Voltra made their first contribution in https://github.com/spatie/crawler/pull/490

**Full Changelog**: https://github.com/spatie/crawler/compare/8.4.3...8.4.4

## 8.4.3 - 2025-05-20

### What's Changed

* Do not try robots.txt when ignored by @kissifrot in https://github.com/spatie/crawler/pull/485

### New Contributors

* @kissifrot made their first contribution in https://github.com/spatie/crawler/pull/485

**Full Changelog**: https://github.com/spatie/crawler/compare/8.4.2...8.4.3

## 8.4.2 - 2025-02-24

### What's Changed

* set spatie/browsershot minimal version to 5.0.5 by @grafst in https://github.com/spatie/crawler/pull/484

### New Contributors

* @grafst made their first contribution in https://github.com/spatie/crawler/pull/484

**Full Changelog**: https://github.com/spatie/crawler/compare/8.4.1...8.4.2

## 8.4.1 - 2025-02-17

**Full Changelog**: https://github.com/spatie/crawler/compare/8.4.0...8.4.1

## 8.4.0 - 2024-12-16

### What's Changed

* Add execution time limit by @VincentLanglet in https://github.com/spatie/crawler/pull/480

### New Contributors

* @VincentLanglet made their first contribution in https://github.com/spatie/crawler/pull/480

**Full Changelog**: https://github.com/spatie/crawler/compare/8.3.1...8.4.0

## 8.3.1 - 2024-12-09

### What's Changed

* Upgrade spatie/browsershot to 5.0 by @hasansoyalan in https://github.com/spatie/crawler/pull/478

### New Contributors

* @hasansoyalan made their first contribution in https://github.com/spatie/crawler/pull/478

**Full Changelog**: https://github.com/spatie/crawler/compare/8.3.0...8.3.1

## 8.3.0 - 2024-12-02

### What's Changed

* Add support for PHP 8.4 by @pascalbaljet in https://github.com/spatie/crawler/pull/477

**Full Changelog**: https://github.com/spatie/crawler/compare/8.2.3...8.3.0

## 8.2.3 - 2024-07-31

### What's Changed

* Fix setParsableMimeTypes() by @superpenguin612 in https://github.com/spatie/crawler/pull/470

**Full Changelog**: https://github.com/spatie/crawler/compare/8.2.2...8.2.3

## 8.2.1 - 2024-07-16

### What's Changed

* Check original URL against depth tree when visited link is a redirect by @superpenguin612 in https://github.com/spatie/crawler/pull/467

### New Contributors

* @superpenguin612 made their first contribution in https://github.com/spatie/crawler/pull/467

**Full Changelog**: https://github.com/spatie/crawler/compare/8.2.0...8.2.1

## 8.2.0 - 2024-02-15

### What's Changed

* Fix wording in documentation by @adamtomat in https://github.com/spatie/crawler/pull/460
* Add Laravel/Illuminate 11 Support by @Jubeki in https://github.com/spatie/crawler/pull/461

### New Contributors

* @adamtomat made their first contribution in https://github.com/spatie/crawler/pull/460
* @Jubeki made their first contribution in https://github.com/spatie/crawler/pull/461

**Full Changelog**: https://github.com/spatie/crawler/compare/8.1.0...8.2.0

## 8.1.0 - 2024-01-02

### What's Changed

* feat: custom link parser by @Velka-DEV in https://github.com/spatie/crawler/pull/458

### New Contributors

* @Velka-DEV made their first contribution in https://github.com/spatie/crawler/pull/458

**Full Changelog**: https://github.com/spatie/crawler/compare/8.0.4...8.1.0

## 8.0.4 - 2023-12-29

- allow Browsershot v4

## 8.0.3 - 2023-11-22

### What's Changed

- Fix return type by @riesjart in https://github.com/spatie/crawler/pull/452

### New Contributors

- @riesjart made their first contribution in https://github.com/spatie/crawler/pull/452

**Full Changelog**: https://github.com/spatie/crawler/compare/8.0.2...8.0.3

## 8.0.2 - 2023-11-20

### What's Changed

- Define only needed methods in observer implementation by @buismaarten in https://github.com/spatie/crawler/pull/449

### New Contributors

- @buismaarten made their first contribution in https://github.com/spatie/crawler/pull/449

**Full Changelog**: https://github.com/spatie/crawler/compare/8.0.1...8.0.2

## 8.0.1 - 2023-07-19

### What's Changed

- Check if rel attribute contains nofollow by @robbinbenard in https://github.com/spatie/crawler/pull/445

### New Contributors

- @robbinbenard made their first contribution in https://github.com/spatie/crawler/pull/445

**Full Changelog**: https://github.com/spatie/crawler/compare/8.0.0...8.0.1

## 8.0.0 - 2023-06-04

- add linkText to crawl observer methods
- upgrade dependencies

## 7.1.3 - 2023-01-24

- support Laravel 10

## 7.1.2 - 2022-05-30

### What's Changed

- Feat/convert phpunit tests to pest by @mansoorkhan96 in https://github.com/spatie/crawler/pull/401
- Add the ability to change the default baseUrl scheme by @arnissolle in https://github.com/spatie/crawler/pull/402

### New Contributors

- @arnissolle made their first contribution in https://github.com/spatie/crawler/pull/402

**Full Changelog**: https://github.com/spatie/crawler/compare/7.1.1...7.1.2

## 7.1.1 - 2022-03-20

## What's Changed

- Fix issue #395 by @BrokenSourceCode in https://github.com/spatie/crawler/pull/396

## New Contributors

- @BrokenSourceCode made their first contribution in https://github.com/spatie/crawler/pull/396

**Full Changelog**: https://github.com/spatie/crawler/compare/7.1.0...7.1.1

## 7.1.0 - 2022-01-14

- allow Laravel 9 collections

## 7.0.5 - 2021-11-15

## What's Changed

- Keep only guzzlehttp/psr7 v2.0 by @flangofas in https://github.com/spatie/crawler/pull/392

## New Contributors

- @flangofas made their first contribution in https://github.com/spatie/crawler/pull/392

**Full Changelog**: https://github.com/spatie/crawler/compare/7.0.4...7.0.5

## 7.0.2 - 2021-09-14

- allow psr7 v2

## 7.0.1 - 2021-08-01

- change response type hint (#371)

## 7.0.0 - 2021-04-27

- require PHP 8+
- drop support for PHP 7.x
- convert syntax to PHP 8
- no API changes have been made

## 6.0.1 - 2021-02-26

- bugfix: infinite loops when a CrawlProfile prevents crawling (#358)

## 6.0.0 - 2020-12-02

- add `setCurrentCrawlLimit` and `setTotalCrawlLimit`
- internal refactors

## 5.0.2 - 2020-11-27

- add support for PHP 8.0

## 5.0.1 - 2020-10-09

- tweak variable naming in `ArrayCrawlQueue` (#326)

## 5.0.0 - 2020-09-29

- improve chucked reading of response
- move observer / profiles / queues to separate namespaces
- typehint all the things
- use laravel/collections instead of tightenco package
- remove support for anything below PHP 7.4
- remove all deprecated functions and classes

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
