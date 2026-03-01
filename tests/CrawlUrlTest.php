<?php

use Spatie\Crawler\CrawlUrl;

it('creates a CrawlUrl instance', function () {
    $crawlUrl = new CrawlUrl(
        url: 'https://example.com/some/test-uri',
        foundOnUrl: 'https://example.com/',
        linkText: 'Some link text',
    );

    expect($crawlUrl)->toBeInstanceOf(CrawlUrl::class);
    expect($crawlUrl->url)->toBe('https://example.com/some/test-uri');
    expect($crawlUrl->foundOnUrl)->toBe('https://example.com/');
    expect($crawlUrl->linkText)->toBe('Some link text');
});

it('has a default depth of 0', function () {
    $crawlUrl = new CrawlUrl(url: 'https://example.com');

    expect($crawlUrl->depth)->toBe(0);
});

it('can set a custom depth', function () {
    $crawlUrl = new CrawlUrl(
        url: 'https://example.com',
        depth: 3,
    );

    expect($crawlUrl->depth)->toBe(3);
});

it('can get and set an id', function () {
    $crawlUrl = new CrawlUrl(
        url: 'https://example.com',
        id: 'custom-id',
    );

    expect($crawlUrl->id)->toBe('custom-id');

    $crawlUrl->id = 'new-id';
    expect($crawlUrl->id)->toBe('new-id');
});

it('has a null id by default', function () {
    $crawlUrl = new CrawlUrl(url: 'https://example.com');

    expect($crawlUrl->id)->toBeNull();
});
