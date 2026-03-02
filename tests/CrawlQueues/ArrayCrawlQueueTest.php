<?php

use Spatie\Crawler\CrawlQueues\ArrayCrawlQueue;
use Spatie\Crawler\CrawlUrl;

beforeEach(function () {
    $this->crawlQueue = new ArrayCrawlQueue;
});

test('a url can be added to crawl queue', function () {
    $crawlUrl = new CrawlUrl('https://example.com');

    $this->crawlQueue->add($crawlUrl);

    expect($this->crawlQueue->getPendingUrl())
        ->toBe($crawlUrl);
});

it('can determine if there are pending urls', function () {
    expect($this->crawlQueue->hasPendingUrls())
        ->toBeFalse();

    $this
        ->crawlQueue
        ->add(new CrawlUrl('https://example.com'));

    expect($this->crawlQueue->hasPendingUrls())
        ->toBeTrue();
});

it('can get an url at the specified index', function () {
    $url1 = new CrawlUrl('https://example1.com/');
    $url2 = new CrawlUrl('https://example2.com/');

    $this->crawlQueue->add($url1);
    $this->crawlQueue->add($url2);

    $urlInCrawlQueue = $this->crawlQueue->getUrlById($url1->id)->url;

    expect($urlInCrawlQueue)
        ->toBe('https://example1.com/');

    $urlInCrawlQueue = $this->crawlQueue->getUrlById($url2->id)->url;

    expect($urlInCrawlQueue)
        ->toBe('https://example2.com/');
});

it('can determine if has a given url', function () {
    expect($this->crawlQueue->has('https://example1.com/'))
        ->toBeFalse();

    $crawlUrl = new CrawlUrl('https://example1.com/');
    $this->crawlQueue->add($crawlUrl);

    expect($this->crawlQueue->has('https://example1.com/'))
        ->toBeTrue();
});

it('can mark a url as processed', function () {
    $crawlUrl = new CrawlUrl('https://example1.com/');

    expect($this->crawlQueue->hasAlreadyBeenProcessed($crawlUrl))
        ->toBeFalse();

    $this->crawlQueue->add($crawlUrl);

    expect($this->crawlQueue->hasAlreadyBeenProcessed($crawlUrl))
        ->toBeFalse();

    $this->crawlQueue->markAsProcessed($crawlUrl);

    expect($this->crawlQueue->hasAlreadyBeenProcessed($crawlUrl))
        ->toBeTrue();
});

it('can remove all processed urls from the pending urls', function () {
    $crawlUrl1 = new CrawlUrl('https://example1.com/');
    $crawlUrl2 = new CrawlUrl('https://example2.com/');

    $this->crawlQueue
        ->add($crawlUrl1)
        ->add($crawlUrl2);

    $this->crawlQueue->markAsProcessed($crawlUrl1);

    $pendingUrlCount = 0;

    while ($url = $this->crawlQueue->getPendingUrl()) {
        $pendingUrlCount++;
        $this->crawlQueue->markAsProcessed($url);
    }

    expect($pendingUrlCount)->toBe(1);
});
