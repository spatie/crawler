<?php

namespace Spatie\Crawler\Test;

use Spatie\Crawler\CrawlObserver;
use Spatie\Crawler\Url;

class CrawlLogger implements CrawlObserver
{
    /**
     * Called when the crawler will crawl the url.
     *
     * @param \Spatie\Crawler\Url   $url
     */
    public function willCrawl(Url $url)
    {
        CrawlerTest::log("willCrawl: {$url}");
    }

    /**
     * Called when the crawler has crawled the given url.
     *
     * @param \Spatie\Crawler\Url   $url
     * @param \Psr\Http\Message\ResponseInterface|null $response
     */
    public function hasBeenCrawled(Url $url, $response)
    {
        CrawlerTest::log("hasBeenCrawled: {$url}");
    }

    /**
     * Called when the crawl has ended.
     */
    public function finishedCrawling()
    {
        CrawlerTest::log('finished crawling');
    }
}
