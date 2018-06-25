<?php

namespace Spatie\Crawler\Handlers;

use GuzzleHttp\Exception\RequestException;
use Spatie\Crawler\Crawler;

class CrawlRequestFailed
{
    /** @var \Spatie\Crawler\Crawler */
    protected $crawler;

    public function __construct(Crawler $crawler)
    {
        $this->crawler = $crawler;
    }

    public function __invoke(RequestException $exception, $index)
    {
        $crawlUrl = $this->crawler->getCrawlQueue()->getUrlById($index);

        $this->crawler->getCrawlObservers()->crawlFailed($crawlUrl, $exception);
    }
}
