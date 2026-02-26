<?php

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Spatie\Crawler\CrawlObservers\CrawlObserver;
use Spatie\Crawler\CrawlObservers\CrawlObserverCollection;
use Spatie\Crawler\CrawlResponse;
use Spatie\Crawler\CrawlUrl;

beforeEach(function () {
    $this->crawlObserver = new class extends CrawlObserver
    {
        public $crawled = false;

        public $failed = false;

        public function crawled(
            string $url,
            CrawlResponse $response,
            ?string $foundOnUrl = null,
            ?string $linkText = null,
        ): void {
            $this->crawled = true;
        }

        public function crawlFailed(
            string $url,
            RequestException $requestException,
            ?string $foundOnUrl = null,
            ?string $linkText = null,
        ): void {
            $this->failed = true;
        }
    };
});

it('can be fulfilled', function () {
    $observers = new CrawlObserverCollection([
        $this->crawlObserver,
    ]);

    $observers->crawled(
        CrawlUrl::create('https://example.com'),
        new CrawlResponse(new Response)
    );

    expect($this->crawlObserver)
        ->crawled->toBeTrue()
        ->failed->toBeFalse();
});

it('can fail', function () {
    $observers = new CrawlObserverCollection([
        $this->crawlObserver,
    ]);

    $observers->crawlFailed(
        CrawlUrl::create('https://example.com'),
        new RequestException('', new Request('GET', 'https://example.com'))
    );

    expect($this->crawlObserver)
        ->crawled->toBeFalse()
        ->failed->toBeTrue();
});

it('can dispatch willCrawl callback', function () {
    $observers = new CrawlObserverCollection;

    $willCrawlUrl = null;
    $willCrawlLinkText = null;

    $observers->onWillCrawl(function (string $url, ?string $linkText) use (&$willCrawlUrl, &$willCrawlLinkText) {
        $willCrawlUrl = $url;
        $willCrawlLinkText = $linkText;
    });

    $observers->willCrawl(
        CrawlUrl::create('https://example.com', linkText: 'Example')
    );

    expect($willCrawlUrl)->toBe('https://example.com');
    expect($willCrawlLinkText)->toBe('Example');
});

it('can dispatch closure callbacks', function () {
    $observers = new CrawlObserverCollection;

    $crawledUrl = null;

    $observers->onCrawled(function (string $url) use (&$crawledUrl) {
        $crawledUrl = $url;
    });

    $observers->crawled(
        CrawlUrl::create('https://example.com'),
        new CrawlResponse(new Response)
    );

    expect($crawledUrl)->toBe('https://example.com');
});

it('can dispatch finished callback', function () {
    $observers = new CrawlObserverCollection;

    $finished = false;

    $observers->onFinished(function () use (&$finished) {
        $finished = true;
    });

    $observers->finishedCrawling();

    expect($finished)->toBeTrue();
});
