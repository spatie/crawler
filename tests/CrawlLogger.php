<?php

namespace Spatie\Crawler\Test;

use Psr\Http\Message\ResponseInterface;
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
     * @param \Spatie\Crawler\Url $url
     * @param \Psr\Http\Message\ResponseInterface|null $response
     * @param \Spatie\Crawler\Url $foundOnUrl
     */
    public function hasBeenCrawled(Url $url, ResponseInterface $response, Url $foundOnUrl = null)
    {
        $logText = "hasBeenCrawled: {$url}";

        if ($foundOnUrl) {
            $logText .= " - found on {$foundOnUrl}";
        }

        CrawlerTest::log($logText);
    }

    /**
     * Called when the crawl has ended.
     */
    public function finishedCrawling()
    {
        CrawlerTest::log('finished crawling');
    }
}
