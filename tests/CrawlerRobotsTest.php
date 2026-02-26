<?php

use Spatie\Crawler\Crawler;
use Spatie\Crawler\Test\TestClasses\Log;

beforeEach(function () {
    Log::reset();
});

it('should not follow robots txt disallowed links', function () {
    createCrawler()
        ->fake(fullSiteFakes())
        ->start();

    expect(['url' => 'https://example.com/txt-disallow', 'foundOn' => 'https://example.com/'])
        ->notToBeCrawled();
});

it('does not allow a root ignored url', function () {
    createCrawler('https://example.com/txt-disallow')
        ->fake(fullSiteFakes())
        ->start();

    expect(['url' => 'https://example.com/txt-disallow', 'foundOn' => 'https://example.com/'])
        ->notToBeCrawled();
});

it('should follow robots txt disallowed links when robots are ignored', function () {
    createCrawler()
        ->fake(fullSiteFakes())
        ->ignoreRobots()
        ->start();

    expect(['url' => 'https://example.com/txt-disallow', 'foundOn' => 'https://example.com/'])
        ->toBeCrawledOnce();
});

it('should follow robots meta follow links', function () {
    createCrawler()
        ->fake(fullSiteFakes())
        ->start();

    expect(['url' => 'https://example.com/meta-nofollow', 'foundOn' => 'https://example.com/meta-follow'])
        ->toBeCrawledOnce();
});

it('should follow robots meta nofollow links when robots are ignored', function () {
    createCrawler()
        ->fake(fullSiteFakes())
        ->ignoreRobots()
        ->start();

    expect(['url' => 'https://example.com/meta-nofollow-target', 'foundOn' => 'https://example.com/meta-nofollow'])
        ->toBeCrawledOnce();
});

it('should not index robots meta noindex', function () {
    createCrawler()
        ->fake(fullSiteFakes())
        ->start();

    $urls = [
        ['url' => 'https://example.com/meta-nofollow', 'foundOn' => 'https://example.com/meta-follow'],
        ['url' => 'https://example.com/meta-follow'],
    ];

    expect($urls)
        ->sequence(
            function ($url) {
                $url->toBeCrawledOnce();
            },
            function ($url) {
                $url->notToBeCrawled();
            },
        );
});

it('should index robots meta noindex when robots are ignored', function () {
    createCrawler()
        ->fake(fullSiteFakes())
        ->ignoreRobots()
        ->start();

    expect(['url' => 'https://example.com/meta-follow', 'foundOn' => 'https://example.com/'])
        ->toBeCrawledOnce();
});

it('should not follow robots header disallowed links', function () {
    createCrawler()
        ->fake(fullSiteFakes())
        ->start();

    expect(['url' => 'https://example.com/header-disallow', 'foundOn' => 'https://example.com/'])
        ->notToBeCrawled();
});

it('should follow robots header disallowed links when robots are ignored', function () {
    createCrawler()
        ->fake(fullSiteFakes())
        ->ignoreRobots()
        ->start();

    expect(['url' => 'https://example.com/header-disallow', 'foundOn' => 'https://example.com/'])
        ->toBeCrawledOnce();
});

it('should check depth when respecting robots', function () {
    createCrawler()
        ->fake(fullSiteFakes())
        ->respectRobots()
        ->depth(1)
        ->start();

    expect(['url' => 'https://example.com/link3', 'foundOn' => 'https://example.com/link2'])
        ->notToBeCrawled();
});

it('should not return RobotsTxt instance when not respecting robots', function () {
    $crawler = Crawler::create('https://example.com')
        ->fake(fullSiteFakes())
        ->ignoreRobots();
    $crawler->start();

    expect($crawler->getRobotsTxt())
        ->toBe(null);
});

it('should return the already set user agent', function () {
    $crawler = Crawler::create()
        ->userAgent('test/1.2.3');

    expect($crawler->getUserAgent())
        ->toBe('test/1.2.3');
});

it('should return the last set user agent', function () {
    $crawler = Crawler::create()
        ->userAgent('test/4.5.6');

    expect($crawler->getUserAgent())
        ->toBe('test/4.5.6');
});

it('should return default user agent when none is set', function () {
    $crawler = Crawler::create();

    expect($crawler->getUserAgent())
        ->toBeNotEmpty();
});

it('should change the default base url scheme to https', function () {
    $crawler = Crawler::create()
        ->defaultScheme('https');

    expect($crawler->getDefaultScheme())
        ->toEqual('https');
});

it('should remember settings', function () {
    $crawler = Crawler::create()
        ->depth(10)
        ->limit(10)
        ->userAgent('test/1.2.3');

    expect($crawler->getMaximumDepth())
        ->toBe(10);

    expect($crawler->getTotalCrawlLimit())
        ->toBe(10);

    expect($crawler->getUserAgent())
        ->toBe('test/1.2.3');
});

it('should check depth when ignoring robots', function () {
    createCrawler()
        ->fake(fullSiteFakes())
        ->ignoreRobots()
        ->depth(1)
        ->start();

    expect(['url' => 'https://example.com/link3', 'foundOn' => 'https://example.com/link2'])
        ->notToBeCrawled();
});

it('should respect custom user agent rules', function () {
    createCrawler()
        ->fake(fullSiteFakes())
        ->respectRobots()
        ->depth(1)
        ->userAgent('my-agent')
        ->start();

    $urls = [
        ['url' => 'https://example.com/txt-disallow-custom-user-agent', 'foundOn' => 'https://example.com/'],
        ['url' => 'https://example.com/txt-disallow', 'foundOn' => 'https://example.com/'],
        ['url' => 'https://example.com/link1', 'foundOn' => 'https://example.com/'],
    ];

    expect($urls)
        ->sequence(
            function ($url) {
                $url->notToBeCrawled();
            },
            function ($url) {
                $url->notToBeCrawled();
            },
            function ($url) {
                $url->toBeCrawledOnce();
            },
        );
});
