<?php

namespace Spatie\Crawler;

use Psr\Http\Message\UriInterface;

class EmptyCrawlObserver implements CrawlObserver
{
    /**
     * Called when the crawler will crawl the url.
     *
     * @param \Psr\Http\Message\UriInterface $url
     *
     * @return void
     */
    public function willCrawl(UriInterface $url)
    {
    }

    /**
     * Called when the crawler has crawled the given url.
     *
     * @param \Psr\Http\Message\UriInterface $url
     * @param \Psr\Http\Message\ResponseInterface|null $response
     * @param \Psr\Http\Message\UriInterface $foundOnUrl
     *
     * @return void
     */
    public function hasBeenCrawled(UriInterface $url, $response, UriInterface $foundOnUrl = null)
    {
    }

    /**
     * Called when the crawl has ended.
     *
     * @return void
     */
    public function finishedCrawling()
    {
    }
}
