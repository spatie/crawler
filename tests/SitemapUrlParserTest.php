<?php

use Spatie\Crawler\Test\TestClasses\Log;
use Spatie\Crawler\UrlParsers\SitemapUrlParser;

beforeEach(function () {
    skipIfTestServerIsNotRunning();

    Log::reset();
});

it('should extract links from sitemap locations', function () {
    createCrawler()
        ->setUrlParserClass(SitemapUrlParser::class)
        ->startCrawling('http://localhost:8080/sitemap.xml');

    expect(['url' => 'http://localhost:8080/link1-prev', 'foundOn' => 'http://localhost:8080/sitemap.xml'])
        ->toBeCrawledOnce();

    expect(['url' => 'http://localhost:8080/link2', 'foundOn' => 'http://localhost:8080/sitemap.xml'])
        ->toBeCrawledOnce();
});
