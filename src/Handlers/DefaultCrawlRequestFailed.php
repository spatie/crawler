<?php

namespace Spatie\Crawler\Handlers;

use GuzzleHttp\Exception\RequestException;

class DefaultCrawlRequestFailed extends CrawlRequestFailed
{
    public function __invoke(RequestException $exception, $index)
    {
        $crawlUrl = $this->crawler->getCrawlQueue()->getUrlById($index);

        $this->crawler->getCrawlObservers()->crawlFailed($crawlUrl, $exception);
    }
}
