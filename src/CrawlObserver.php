<?php

namespace Spatie\Crawler;

use Psr\Http\Message\ResponseInterface;

interface CrawlObserver
{
    /*
     * Called when the crawler will crawl the url.
     */
    public function willCrawl(Url $url);

    /*
     * Called when the crawler has crawled the given url.
     */
    public function hasBeenCrawled(Url $url, ResponseInterface $response, Url $foundOnUrl = null);

    /*
     * Called when the crawl has ended.
     */
    public function finishedCrawling();
}
