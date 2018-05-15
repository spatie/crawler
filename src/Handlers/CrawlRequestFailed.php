<?php

namespace Spatie\Crawler\Handlers;

use Spatie\Crawler\CrawlQueue\CrawlQueue;
use GuzzleHttp\Exception\RequestException;

class CrawlRequestFailed
{
    /** @var \Spatie\Crawler\CrawlQueue\CrawlQueue */
    protected $crawlQueue;

    /** @var array[\Spatie\Crawler\CrawlObserver] */
    protected $crawlObservers;

    public function __construct(
        CrawlQueue $crawlQueue,
        array $crawlObservers
    ) {
        $this->crawlQueue = $crawlQueue;
        $this->crawlObservers = $crawlObservers;
    }

    public function __invoke(RequestException $exception, $index)
    {
        $crawlUrl = $this->crawlQueue->getUrlById($index);

        foreach ($this->crawlObservers as $crawlObserver) {
            $crawlObserver->crawlFailed(
                $crawlUrl->url,
                $exception,
                $crawlUrl->foundOnUrl
            );
        }
    }
}
