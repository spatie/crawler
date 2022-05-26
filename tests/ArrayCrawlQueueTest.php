<?php

use GuzzleHttp\Psr7\Uri;
use Spatie\Crawler\CrawlQueues\ArrayCrawlQueue;
use Spatie\Crawler\CrawlUrl;

beforeEach(function () {
    $this->crawlQueue = new ArrayCrawlQueue();
});

test('a url can be added to crawl queue', function () {
    $crawlUrl = createCrawlUrl('https://example.com');

    $this->crawlQueue->add($crawlUrl);

    expect($this->crawlQueue->getPendingUrl())
        ->toBe($crawlUrl);
});

it('can determine if there are pending urls', function () {
    expect($this->crawlQueue->hasPendingUrls())
        ->toBeFalse();

    $this
        ->crawlQueue
        ->add(createCrawlUrl('https://example.com'));

    expect($this->crawlQueue->hasPendingUrls())
        ->toBeTrue();
});

it('can get an url at the specified index', function () {
    $url1 = createCrawlUrl('https://example1.com/');
    $url2 = createCrawlUrl('https://example2.com/');

    $this->crawlQueue->add($url1);
    $this->crawlQueue->add($url2);

    $urlInCrawlQueue = (string) $this->crawlQueue->getUrlById($url1->getId())->url;

    expect($urlInCrawlQueue)
        ->toBe('https://example1.com/');

    $urlInCrawlQueue = (string) $this->crawlQueue->getUrlById($url2->getId())->url;

    expect($urlInCrawlQueue)
        ->toBe('https://example2.com/');
});

it('can determine if has a given url', function () {
    $crawlUrl = createCrawlUrl('https://example1.com/');

    expect($this->crawlQueue->has($crawlUrl))
        ->toBeFalse();

    $this->crawlQueue->add($crawlUrl);

    expect($this->crawlQueue->has($crawlUrl))
        ->toBeTrue();
});

it('can mark a url as processed', function () {
    $crawlUrl = createCrawlUrl('https://example1.com/');

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
    $crawlUrl1 = createCrawlUrl('https://example1.com/');
    $crawlUrl2 = createCrawlUrl('https://example2.com/');

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

function createCrawlUrl(string $url): CrawlUrl
{
    return CrawlUrl::create(new Uri($url));
}
