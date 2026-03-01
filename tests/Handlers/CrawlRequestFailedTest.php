<?php

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlQueues\ArrayCrawlQueue;
use Spatie\Crawler\CrawlUrl;
use Spatie\Crawler\Handlers\CrawlRequestFailed;

it('preserves the original request from ConnectException', function () {
    $capturedExceptions = [];

    $crawlUrl = new CrawlUrl('https://example.com/timeout');

    $queue = new ArrayCrawlQueue;
    $queue->add($crawlUrl);

    $originalRequest = new Request('GET', 'https://example.com/timeout', [
        'X-Started-At' => '1709337600',
    ]);

    $connectException = new ConnectException('Connection timed out', $originalRequest);

    $crawler = Crawler::create('https://example.com')
        ->onFailed(function (
            string $url,
            RequestException $exception,
        ) use (&$capturedExceptions) {
            $capturedExceptions[] = $exception;
        });

    $crawler->crawlQueue($queue);

    $handler = new CrawlRequestFailed($crawler);
    $handler($connectException, $crawlUrl->id);

    expect($capturedExceptions)->toHaveCount(1);

    $wrappedException = $capturedExceptions[0];
    expect($wrappedException)->toBeInstanceOf(RequestException::class);
    expect($wrappedException->getRequest()->getHeaderLine('X-Started-At'))->toBe('1709337600');
    expect($wrappedException->getPrevious())->toBe($connectException);
});

it('creates a fresh request for non-ConnectException errors', function () {
    $capturedExceptions = [];

    $crawlUrl = new CrawlUrl('https://example.com/error');

    $queue = new ArrayCrawlQueue;
    $queue->add($crawlUrl);

    $genericException = new RuntimeException('Something went wrong');

    $crawler = Crawler::create('https://example.com')
        ->onFailed(function (
            string $url,
            RequestException $exception,
        ) use (&$capturedExceptions) {
            $capturedExceptions[] = $exception;
        });

    $crawler->crawlQueue($queue);

    $handler = new CrawlRequestFailed($crawler);
    $handler($genericException, $crawlUrl->id);

    expect($capturedExceptions)->toHaveCount(1);

    $wrappedException = $capturedExceptions[0];
    expect($wrappedException)->toBeInstanceOf(RequestException::class);
    expect($wrappedException->getRequest()->getUri()->__toString())->toBe('https://example.com/error');
    expect($wrappedException->getRequest()->hasHeader('X-Started-At'))->toBeFalse();
    expect($wrappedException->getPrevious())->toBe($genericException);
});
