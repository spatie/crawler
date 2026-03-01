<?php

namespace Spatie\Crawler\Handlers;

use Exception;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Spatie\Crawler\Crawler;

class CrawlRequestFailed
{
    public function __construct(protected Crawler $crawler) {}

    public function __invoke(Exception $exception, mixed $index): void
    {
        if ($exception instanceof ConnectException) {
            $exception = new RequestException($exception->getMessage(), $exception->getRequest());
        }

        if ($exception instanceof RequestException) {
            $crawlUrl = $this->crawler->getCrawlQueue()->getUrlById($index);

            $this->crawler->recordFailed();
            $this->crawler->getCrawlObservers()->crawlFailed($crawlUrl, $exception, $this->crawler->getCrawlProgress());
        }

        $this->crawler->applyDelay();
    }
}
