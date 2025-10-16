<?php

use GuzzleHttp\Psr7\Uri;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlUrl;
use Spatie\Crawler\Test\TestClasses\Log;

beforeEach(function () {
    skipIfTestServerIsNotRunning();

    Log::reset();
});

describe('::create($url, ?$foundOnUrl, ?$id, ?$linkText)', function () {
    it('creates a CrawlUrl instance that isn\'t in an invalid state when no $id is provided', function () {
        $crawlUrl = CrawlUrl::create(
            url: new Uri('/some/test-uri'),
            foundOnUrl: new Uri('/'),
            linkText: 'Some link text',
        );

        expect($crawlUrl)->toBeInstanceOf(CrawlUrl::class);

        expect(fn() => $crawlUrl->getId())->not->toThrow(\Throwable::class);
        expect(fn() => $crawlUrl->getId())->not->toThrow(\Error::class);
    });
});
