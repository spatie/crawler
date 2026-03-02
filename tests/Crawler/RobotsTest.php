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

    expect(['url' => 'https://example.com/meta-nofollow', 'foundOn' => 'https://example.com/meta-follow'])
        ->toBeCrawledOnce();

    expect(['url' => 'https://example.com/meta-follow'])
        ->notToBeCrawled();
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

    expect([
        ['url' => 'https://example.com/txt-disallow-custom-user-agent', 'foundOn' => 'https://example.com/'],
        ['url' => 'https://example.com/txt-disallow', 'foundOn' => 'https://example.com/'],
    ])->each->notToBeCrawled();

    expect(['url' => 'https://example.com/link1', 'foundOn' => 'https://example.com/'])
        ->toBeCrawledOnce();
});
