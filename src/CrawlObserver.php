<?php

namespace Spatie\Crawler;

interface CrawlObserver
{
    /**
     * Called when the crawler will crawl the url.
     *
     * @param \Spatie\Crawler\CrawlUrl $url
     *
     * @return void
     */
    public function willCrawl(CrawlUrl $url);

    /**
     * Called when the crawler has crawled the given url.
     *
     * @param \Spatie\Crawler\CrawlUrl $url
     * @param \Psr\Http\Message\ResponseInterface|null $response
     *
     * @return void
     */
    public function hasBeenCrawled(CrawlUrl $url, $response);

    /**
     * Called when the crawl has ended.
     *
     * @return void
     */
    public function finishedCrawling();
}
