<?php

namespace Spatie\Crawler\Test;

use Psr\Http\Message\UriInterface;
use Spatie\Crawler\Url;
use Spatie\Crawler\CrawlObserver;

class CrawlLogger implements CrawlObserver
{
    /**
     * Called when the crawler will crawl the url.
     *
     * @param UriInterface   $url
     */
    public function willCrawl(UriInterface $url)
    {
        CrawlerTest::log("willCrawl: {$url}");
    }

    /**
     * Called when the crawler has crawled the given url.
     *
     * @param UriInterface $url
     * @param \Psr\Http\Message\ResponseInterface|null $response
     * @param UriInterface $foundOnUrl
     */
    public function hasBeenCrawled(UriInterface $url, $response, UriInterface $foundOnUrl = null)
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
