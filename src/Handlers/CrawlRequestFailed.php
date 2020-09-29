<?php

namespace Spatie\Crawler\Handlers;

use Exception;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Spatie\Crawler\Crawler;

class CrawlRequestFailed
{
    protected Crawler $crawler;

    public function __construct(Crawler $crawler)
    {
        $this->crawler = $crawler;
    }

    public function __invoke(Exception $exception, $index)
    {
        if ($exception instanceof ConnectException) {
            $exception = new RequestException('', $exception->getRequest());
        }

        if ($exception instanceof RequestException) {
            $crawlUrl = $this->crawler->getCrawlQueue()->getUrlById($index);

            $this->crawler->getCrawlObservers()->crawlFailed($crawlUrl, $exception);
        }

        usleep($this->crawler->getDelayBetweenRequests());
    }
}
