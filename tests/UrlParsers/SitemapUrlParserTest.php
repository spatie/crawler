<?php

use Spatie\Crawler\Test\TestClasses\Log;

beforeEach(function () {
    Log::reset();
});

it('should extract child sitemaps from sitemap index', function () {
    createCrawler('https://example.com/sitemap_index.xml')
        ->fake(sitemapFakes())
        ->parseSitemaps()
        ->start();

    expect([
        ['url' => 'https://example.com/sitemap1.xml', 'foundOn' => 'https://example.com/sitemap_index.xml'],
        ['url' => 'https://example.com/sitemap2.xml', 'foundOn' => 'https://example.com/sitemap_index.xml'],
    ])->each->toBeCrawledOnce();
});

it('should extract urls from sitemaps through sitemap index', function () {
    createCrawler('https://example.com/sitemap_index.xml')
        ->fake(sitemapFakes())
        ->parseSitemaps()
        ->start();

    expect([
        ['url' => 'https://example.com/', 'foundOn' => 'https://example.com/sitemap1.xml'],
        ['url' => 'https://example.com/link1', 'foundOn' => 'https://example.com/sitemap1.xml'],
        ['url' => 'https://example.com/link1-next', 'foundOn' => 'https://example.com/sitemap2.xml'],
        ['url' => 'https://example.com/link1-prev', 'foundOn' => 'https://example.com/sitemap2.xml'],
        ['url' => 'https://example.com/link2', 'foundOn' => 'https://example.com/sitemap2.xml'],
        ['url' => 'https://example.com/link3', 'foundOn' => 'https://example.com/sitemap2.xml'],
    ])->each->toBeCrawledOnce();
});
