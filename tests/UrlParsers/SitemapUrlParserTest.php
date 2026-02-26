<?php

use Spatie\Crawler\Test\TestClasses\Log;

beforeEach(function () {
    skipIfTestServerIsNotRunning();

    Log::reset();
});

it('should extract child sitemaps from sitemap index', function () {
    createCrawler()
        ->parseSitemaps()
        ->startCrawling('http://localhost:8080/sitemap_index.xml');

    expect(['url' => 'http://localhost:8080/sitemap1.xml', 'foundOn' => 'http://localhost:8080/sitemap_index.xml'])
        ->toBeCrawledOnce();

    expect(['url' => 'http://localhost:8080/sitemap2.xml', 'foundOn' => 'http://localhost:8080/sitemap_index.xml'])
        ->toBeCrawledOnce();
});

it('should extract urls from sitemaps trough sitemap index', function () {
    createCrawler()
        ->parseSitemaps()
        ->startCrawling('http://localhost:8080/sitemap_index.xml');

    expect(['url' => 'http://localhost:8080/', 'foundOn' => 'http://localhost:8080/sitemap1.xml'])
        ->toBeCrawledOnce();

    expect(['url' => 'http://localhost:8080/link1', 'foundOn' => 'http://localhost:8080/sitemap1.xml'])
        ->toBeCrawledOnce();

    expect(['url' => 'http://localhost:8080/link1-next', 'foundOn' => 'http://localhost:8080/sitemap2.xml'])
        ->toBeCrawledOnce();

    expect(['url' => 'http://localhost:8080/link1-prev', 'foundOn' => 'http://localhost:8080/sitemap2.xml'])
        ->toBeCrawledOnce();

    expect(['url' => 'http://localhost:8080/link2', 'foundOn' => 'http://localhost:8080/sitemap2.xml'])
        ->toBeCrawledOnce();

    expect(['url' => 'http://localhost:8080/link3', 'foundOn' => 'http://localhost:8080/sitemap2.xml'])
        ->toBeCrawledOnce();
});
