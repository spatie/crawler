<?php

use Spatie\Crawler\Crawler;
use Spatie\Crawler\Test\TestClasses\Log;

beforeEach(function () {
    skipIfTestServerIsNotRunning();

    Log::reset();
});

it('should not follow robots txt disallowed links', function () {
    createCrawler()->startCrawling('http://localhost:8080');

    expect(['url' => 'http://localhost:8080/txt-disallow', 'foundOn' => 'http://localhost:8080/'])
        ->notToBeCrawled();
});

it('does not allow a root ignored url', function () {
    createCrawler()->startCrawling('http://localhost:8080/txt-disallow');

    expect(['url' => 'http://localhost:8080/txt-disallow', 'foundOn' => 'http://localhost:8080/'])
        ->notToBeCrawled();
});

it('should follow robots txt disallowed links when robots are ignored', function () {
    createCrawler()
        ->ignoreRobots()
        ->startCrawling('http://localhost:8080');

    expect(['url' => 'http://localhost:8080/txt-disallow', 'foundOn' => 'http://localhost:8080/'])
        ->toBeCrawledOnce();
});

it('should follow robots meta follow links', function () {
    createCrawler()->startCrawling('http://localhost:8080');

    expect(['url' => 'http://localhost:8080/meta-nofollow', 'foundOn' => 'http://localhost:8080/meta-follow'])
        ->toBeCrawledOnce();
});

it('should follow robots meta nofollow links when robots are ignored', function () {
    createCrawler()
        ->ignoreRobots()
        ->startCrawling('http://localhost:8080');

    expect(['url' => 'http://localhost:8080/meta-nofollow-target', 'foundOn' => 'http://localhost:8080/meta-nofollow'])
        ->toBeCrawledOnce();
});

it('should not index robots meta noindex', function () {
    createCrawler()->startCrawling('http://localhost:8080');

    $urls = [
        ['url' => 'http://localhost:8080/meta-nofollow', 'foundOn' => 'http://localhost:8080/meta-follow'],
        ['url' => 'http://localhost:8080/meta-follow'],
    ];

    expect($urls)
        ->sequence(
            function ($url) { $url->toBeCrawledOnce(); },
            function ($url) { $url->notToBeCrawled(); },
        );
});

it('should index robots meta noindex when robots are ignored', function () {
    createCrawler()
        ->ignoreRobots()
        ->startCrawling('http://localhost:8080');

    expect(['url' => 'http://localhost:8080/meta-follow', 'foundOn' => 'http://localhost:8080/'])
        ->toBeCrawledOnce();
});

it('should not follow robots header disallowed links', function () {
    createCrawler()->startCrawling('http://localhost:8080');

    expect(['url' => 'http://localhost:8080/header-disallow', 'foundOn' => 'http://localhost:8080/'])
        ->notToBeCrawled();
});

it('should follow robots header disallowed links when robots are ignored', function () {
    createCrawler()
        ->ignoreRobots()
        ->startCrawling('http://localhost:8080');

    expect(['url' => 'http://localhost:8080/header-disallow', 'foundOn' => 'http://localhost:8080/'])
        ->toBeCrawledOnce();
});

it('should check depth when respecting robots', function () {
    createCrawler()
        ->respectRobots()
        ->setMaximumDepth(1)
        ->startCrawling('http://localhost:8080');

    expect(['url' => 'http://localhost:8080/link3', 'foundOn' => 'http://localhost:8080/link2'])
        ->notToBeCrawled();
});

it('should return the already set user agent', function () {
    $crawler = Crawler::create()
        ->setUserAgent('test/1.2.3');

    expect($crawler->getUserAgent())
        ->toBe('test/1.2.3');
});

it('should return the user agent set by constructor', function () {
    $crawler = Crawler::create([
        'headers' => ['User-Agent' => 'test/1.2.3'],
    ]);

    expect($crawler->getUserAgent())
        ->toBe('test/1.2.3');
});

it('should return the last set user agent', function () {
    $crawler = Crawler::create(['headers' => ['User-Agent' => 'test/1.2.3']])
        ->setUserAgent('test/4.5.6');

    expect($crawler->getUserAgent())
        ->toBe('test/4.5.6');
});

it('should return default user agent when none is set', function () {
    $crawler = Crawler::create();

    expect($crawler->getUserAgent())
        ->toBeNotEmpty();
});

it('should remember settings', function () {
    $crawler = Crawler::create()
        ->setMaximumDepth(10)
        ->setTotalCrawlLimit(10)
        ->setUserAgent('test/1.2.3');

    expect($crawler->getMaximumDepth())
        ->toBe(10);

    expect($crawler->getTotalCrawlLimit())
        ->toBe(10);

    expect($crawler->getUserAgent())
        ->toBe('test/1.2.3');
});

it('should check depth when ignoring robots', function () {
    createCrawler()
        ->ignoreRobots()
        ->setMaximumDepth(1)
        ->startCrawling('http://localhost:8080');

    expect(['url' => 'http://localhost:8080/link3', 'foundOn' => 'http://localhost:8080/link2'])
        ->notToBeCrawled();
});

it('should respect custom user agent rules', function () {
    // According to Robots docs only
    // one group out of the robots.txt file applies.
    // So wildcard (*) instructions should be ignored
    // by the more specific agent instructions
    // @see https://developers.google.com/search/reference/robots_txt
    // @see https://en.wikipedia.org/wiki/Robots_exclusion_standard

    createCrawler()
        ->respectRobots()
        ->setMaximumDepth(1)
        ->setUserAgent('my-agent')
        ->startCrawling('http://localhost:8080');

    $urls = [
        ['url' => 'http://localhost:8080/txt-disallow-custom-user-agent', 'foundOn' => 'http://localhost:8080/'],
        ['url' => 'http://localhost:8080/txt-disallow', 'foundOn' => 'http://localhost:8080/'],
        ['url' => 'http://localhost:8080/link1', 'foundOn' => 'http://localhost:8080/'],
    ];

    expect($urls)
        ->sequence(
            function ($url) { $url->notToBeCrawled(); },
            function ($url) { $url->notToBeCrawled(); },
            function ($url) { $url->toBeCrawledOnce(); },
        );
});
