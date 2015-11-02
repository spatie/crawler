<?php

namespace Spatie\Crawler;

use GuzzleHttp\Psr7\Response;

interface CrawlObserver
{
    /**
     * Called when the crawl will crawl the url.
     *
     * @param \Spatie\Crawler\Url $url
     */
    public function willCrawl(Url $url);

    /**
     * Called when the crawl will crawl has crawled the given url.
     *
     * @param \Spatie\Crawler\Url       $url
     * @param \GuzzleHttp\Psr7\Response $response
     */
    public function haveCrawled(Url $url, Response $response);

    /**
     * Called when the crawl has ended.
     */
    public function finishedCrawling();
}
