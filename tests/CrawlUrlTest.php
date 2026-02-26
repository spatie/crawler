<?php

use Spatie\Crawler\CrawlUrl;

it('creates a CrawlUrl instance', function () {
    $crawlUrl = CrawlUrl::create(
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
    $crawlUrl = CrawlUrl::create(url: 'https://example.com');

    expect($crawlUrl->depth)->toBe(0);
});

it('can set a custom depth', function () {
    $crawlUrl = CrawlUrl::create(
        url: 'https://example.com',
        depth: 3,
    );

    expect($crawlUrl->depth)->toBe(3);
});

it('can get and set an id', function () {
    $crawlUrl = CrawlUrl::create(
        url: 'https://example.com',
        id: 'custom-id',
    );

    expect($crawlUrl->getId())->toBe('custom-id');

    $crawlUrl->setId('new-id');
    expect($crawlUrl->getId())->toBe('new-id');
});

it('has a null id by default', function () {
    $crawlUrl = CrawlUrl::create(url: 'https://example.com');

    expect($crawlUrl->getId())->toBeNull();
});
