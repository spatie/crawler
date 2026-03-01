<?php

use Spatie\Crawler\CrawlQueues\ArrayCrawlQueue;
use Spatie\Crawler\CrawlUrl;

beforeEach(function () {
    $this->crawlQueue = new ArrayCrawlQueue;
});

it('normalizes scheme to lowercase', function () {
    $this->crawlQueue->add(new CrawlUrl('HTTPS://example.com/page'));

    expect($this->crawlQueue->has('https://example.com/page'))->toBeTrue();
});

it('normalizes host to lowercase', function () {
    $this->crawlQueue->add(new CrawlUrl('https://Example.COM/page'));

    expect($this->crawlQueue->has('https://example.com/page'))->toBeTrue();
});

it('removes default port 80 for http', function () {
    $this->crawlQueue->add(new CrawlUrl('http://example.com:80/page'));

    expect($this->crawlQueue->has('http://example.com/page'))->toBeTrue();
});

it('removes default port 443 for https', function () {
    $this->crawlQueue->add(new CrawlUrl('https://example.com:443/page'));

    expect($this->crawlQueue->has('https://example.com/page'))->toBeTrue();
});

it('keeps non-default ports', function () {
    $this->crawlQueue->add(new CrawlUrl('https://example.com:8080/page'));

    expect($this->crawlQueue->has('https://example.com:8080/page'))->toBeTrue();
    expect($this->crawlQueue->has('https://example.com/page'))->toBeFalse();
});

it('strips trailing slash from non-root paths', function () {
    $this->crawlQueue->add(new CrawlUrl('https://example.com/page/'));

    expect($this->crawlQueue->has('https://example.com/page'))->toBeTrue();
});

it('preserves trailing slash for root path', function () {
    $this->crawlQueue->add(new CrawlUrl('https://example.com/'));

    expect($this->crawlQueue->has('https://example.com/'))->toBeTrue();
});

it('removes empty query strings', function () {
    $this->crawlQueue->add(new CrawlUrl('https://example.com/page?'));

    expect($this->crawlQueue->has('https://example.com/page'))->toBeTrue();
});

it('preserves non-empty query strings', function () {
    $this->crawlQueue->add(new CrawlUrl('https://example.com/page?foo=bar'));

    expect($this->crawlQueue->has('https://example.com/page?foo=bar'))->toBeTrue();
});

it('strips fragments', function () {
    $this->crawlQueue->add(new CrawlUrl('https://example.com/page#section'));

    expect($this->crawlQueue->has('https://example.com/page'))->toBeTrue();
});

it('deduplicates urls that normalize to the same value', function () {
    $this->crawlQueue->add(new CrawlUrl('https://Example.com/page'));
    $this->crawlQueue->add(new CrawlUrl('https://example.com/page/'));
    $this->crawlQueue->add(new CrawlUrl('HTTPS://example.com/page'));
    $this->crawlQueue->add(new CrawlUrl('https://example.com:443/page'));

    $count = 0;
    while ($url = $this->crawlQueue->getPendingUrl()) {
        $count++;
        $this->crawlQueue->markAsProcessed($url);
    }

    expect($count)->toBe(1);
});

it('preserves original url on the CrawlUrl object', function () {
    $originalUrl = 'https://Example.COM:443/Page/';

    $this->crawlQueue->add(new CrawlUrl($originalUrl));

    $pending = $this->crawlQueue->getPendingUrl();

    expect($pending->url)->toBe($originalUrl);
});

it('marks normalized variants as already processed', function () {
    $crawlUrl1 = new CrawlUrl('https://example.com/page');
    $crawlUrl2 = new CrawlUrl('https://Example.com/page/');

    $this->crawlQueue->add($crawlUrl1);
    $this->crawlQueue->markAsProcessed($crawlUrl1);

    expect($this->crawlQueue->hasAlreadyBeenProcessed($crawlUrl2))->toBeTrue();
});

it('can retrieve url by normalized id after adding', function () {
    $crawlUrl = new CrawlUrl('https://Example.com:443/page/');

    $this->crawlQueue->add($crawlUrl);

    $retrieved = $this->crawlQueue->getUrlById($crawlUrl->id);

    expect($retrieved)->toBe($crawlUrl);
    expect($retrieved->url)->toBe('https://Example.com:443/page/');
});

it('does not inflate processed count with normalized duplicates', function () {
    $this->crawlQueue->add(new CrawlUrl('https://example.com/page'));
    $this->crawlQueue->add(new CrawlUrl('https://Example.com/page/'));
    $this->crawlQueue->add(new CrawlUrl('https://example.com:443/page'));

    expect($this->crawlQueue->getProcessedUrlCount())->toBe(0);

    $url = $this->crawlQueue->getPendingUrl();
    $this->crawlQueue->markAsProcessed($url);

    expect($this->crawlQueue->getProcessedUrlCount())->toBe(1);
    expect($this->crawlQueue->hasPendingUrls())->toBeFalse();
});

it('handles malformed urls gracefully', function () {
    $crawlUrl = new CrawlUrl('not-a-valid-url');

    $this->crawlQueue->add($crawlUrl);

    expect($this->crawlQueue->has('not-a-valid-url'))->toBeTrue();
});
