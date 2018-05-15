<?php

namespace Spatie\Crawler\Handlers;

use Spatie\Crawler\Crawler;
use GuzzleHttp\Exception\RequestException;

class CrawlRequestFailed
{
    /** @var \Spatie\Crawler\Crawler */
    private $crawler;

    public function __construct(Crawler $crawler)
    {
        $this->crawler = $crawler;
    }

    public function __invoke(RequestException $exception, $index)
    {
        $crawlUrl = $this->crawler->getCrawlQueue()->getUrlById($index);

        foreach ($this->crawler->getCrawlObservers() as $crawlObserver) {
            $crawlObserver->crawlFailed(
                $crawlUrl->url,
                $exception,
                $crawlUrl->foundOnUrl
            );
        }
    }
}
