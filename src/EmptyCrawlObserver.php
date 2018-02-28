<?php

namespace Spatie\Crawler;

use Psr\Http\Message\UriInterface;
use GuzzleHttp\Exception\RequestException;

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
     * @param RequestException|null $exception
     *
     * @return void
     */
    public function hasBeenCrawled(
        UriInterface $url,
        $response,
        UriInterface $foundOnUrl = null,
        RequestException $exception = null
    ) {
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
