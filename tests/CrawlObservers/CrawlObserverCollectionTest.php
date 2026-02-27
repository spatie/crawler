<?php

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Spatie\Crawler\CrawlObservers\CrawlObserver;
use Spatie\Crawler\CrawlObservers\CrawlObserverCollection;
use Spatie\Crawler\CrawlProgress;
use Spatie\Crawler\CrawlResponse;
use Spatie\Crawler\CrawlUrl;
use Spatie\Crawler\Enums\FinishReason;
use Spatie\Crawler\Enums\ResourceType;

function makeCrawlProgress(): CrawlProgress
{
    return new CrawlProgress(urlsCrawled: 0, urlsFailed: 0, urlsFound: 0, urlsPending: 0);
}

beforeEach(function () {
    $this->crawlObserver = new class extends CrawlObserver
    {
        public $crawled = false;

        public $failed = false;

        public function crawled(
            string $url,
            CrawlResponse $response,
            CrawlProgress $progress,
        ): void {
            $this->crawled = true;
        }

        public function crawlFailed(
            string $url,
            RequestException $requestException,
            CrawlProgress $progress,
            ?string $foundOnUrl = null,
            ?string $linkText = null,
            ?ResourceType $resourceType = null,
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
        new CrawlResponse(new Response),
        makeCrawlProgress(),
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
        new RequestException('', new Request('GET', 'https://example.com')),
        makeCrawlProgress(),
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
        new CrawlResponse(new Response),
        makeCrawlProgress(),
    );

    expect($crawledUrl)->toBe('https://example.com');
});

it('can dispatch finished callback', function () {
    $observers = new CrawlObserverCollection;

    $finished = false;

    $observers->onFinished(function () use (&$finished) {
        $finished = true;
    });

    $observers->finishedCrawling(FinishReason::Completed, makeCrawlProgress());

    expect($finished)->toBeTrue();
});
