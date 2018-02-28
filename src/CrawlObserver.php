<?php

namespace Spatie\Crawler;

use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

abstract class CrawlObserver
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
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param \Psr\Http\Message\UriInterface|null $foundOnUrl
     *
     * @return void
     */
    abstract public function crawled(
        UriInterface $url,
        ResponseInterface $response,
        ?UriInterface $foundOnUrl = null
    );

    abstract public function crawlFailed(
        UriInterface $url,
        RequestException $requestException,
        ?UriInterface $foundOnUrl = null
    );

    /**
     * Called when the crawl has ended.
     *
     * @return void
     */
    public function finishedCrawling()
    {
    }
}
