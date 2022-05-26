<?php

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlObservers\CrawlObserver;
use Spatie\Crawler\CrawlObservers\CrawlObserverCollection;
use Spatie\Crawler\CrawlUrl;

beforeEach(function () {
    $this->crawlObserver = new class () extends CrawlObserver {
        public $crawled = false;

        public $failed = false;

        public function crawled(
            UriInterface $url,
            ResponseInterface $response,
            ?UriInterface $foundOnUrl = null
        ): void {
            $this->crawled = true;
        }

        public function crawlFailed(
            UriInterface $url,
            RequestException $requestException,
            ?UriInterface $foundOnUrl = null
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
        CrawlUrl::create(new Uri('')),
        new Response()
    );

    expect($this->crawlObserver)
        ->crawled->toBeTrue()
        ->failed->toBeFalse();
});

it('can fail', function () {
    $observers = new CrawlObserverCollection([
        $this->crawlObserver,
    ]);

    $uri = new Uri('');

    $observers->crawlFailed(
        CrawlUrl::create(new Uri('')),
        new RequestException('', new Request('GET', $uri))
    );

    expect($this->crawlObserver)
        ->crawled->toBeFalse()
        ->failed->toBeTrue();
});
