<?php

namespace Spatie\Crawler;

use GuzzleHttp\Exception\RequestException;

interface RetryProfile
{
    /**
     * Returns whether a given request should be retried.
     *
     * Note: when using a custom implementation of CrawlQueue, ensure that your class implements RetryableCrawlQueue
     * instead of CrawlQueue to make use of the retry feature, or retries will not be executed.
     *
     * If you're using the default CollectionCrawlQueue, you're all set.
     *
     * @param CrawlUrl         $crawlUrl  The failed URL. Contains the number of attempts to load it.
     * @param RequestException $exception The Guzzle exception that occurred while performing the request.
     *
     * @return bool
     */
    public function shouldRetry(CrawlUrl $crawlUrl, RequestException $exception) : bool;
}
