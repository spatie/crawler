<?php

use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlObservers\CrawlObserver;
use Spatie\Crawler\CrawlProgress;
use Spatie\Crawler\CrawlResponse;

it('closes response body streams after processing', function () {
    $responses = [];

    $observer = new class($responses) extends CrawlObserver
    {
        public function __construct(protected array &$responses) {}

        public function crawled(
            string $url,
            CrawlResponse $response,
            CrawlProgress $progress,
        ): void {
            $this->responses[] = $response->toPsrResponse();
        }
    };

    Crawler::create('https://example.com')
        ->addObserver($observer)
        ->fake([
            'https://example.com' => '<a href="/page1">Page 1</a><a href="/page2">Page 2</a>',
            'https://example.com/page1' => 'Page 1 content',
            'https://example.com/page2' => 'Page 2 content',
        ])
        ->start();

    expect($responses)->toHaveCount(3);

    foreach ($responses as $response) {
        expect($response->getBody()->isReadable())->toBeFalse();
    }
});
