<?php

namespace Spatie\Crawler\Handlers;

use Spatie\Crawler\Crawler;
use GuzzleHttp\Exception\RequestException;

class CrawlRequestFailed
{
    /** @var \Spatie\Crawler\Crawler */
    protected $crawler;

    public function __construct(Crawler $crawler)
    {
        $this->crawler = $crawler;
    }

    public function __invoke(RequestException $exception, string $url)
    {
        $crawlUrl = $this->crawler->getCrawlQueue()->get($url);

        $this->crawler->getCrawlObservers()->crawlFailed($crawlUrl, $exception);

        usleep($this->crawler->getDelayBetweenRequests());
    }
}
