<?php

namespace Spatie\Crawler\Handlers;

use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlQueue\RetryableCrawlQueue;
use GuzzleHttp\Exception\RequestException;

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

        if ($this->crawler->getRetryProfile()->shouldRetry($crawlUrl, $exception)) {
            $crawlQueue = $this->crawler->getCrawlQueue();

            if ($crawlQueue instanceof RetryableCrawlQueue) {
                $crawlQueue->retry($crawlUrl);
            }
        }

        usleep($this->crawler->getDelayBetweenRequests());
    }
}
