<?php

use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlInternalUrls;
use Spatie\Crawler\Test\CrawlLogger;
use Spatie\Crawler\CrawlObserver;
use Psr\Http\Message\UriInterface;

include "vendor/autoload.php";

$observer = new class implements CrawlObserver
{
    /**
     * Called when the crawler will crawl the url.
     *
     * @param \Psr\Http\Message\UriInterface   $url
     */
    public function willCrawl(UriInterface $url)
    {

    }

    /**
     * Called when the crawler has crawled the given url.
     *
     * @param \Psr\Http\Message\UriInterface $url
     * @param \Psr\Http\Message\ResponseInterface|null $response
     * @param \Psr\Http\Message\UriInterface|null $foundOnUrl
     */
    public function hasBeenCrawled(UriInterface $url, $response, ?UriInterface $foundOnUrl = null)
    {

    }

    /**
     * Called when the crawl has ended.
     */
    public function finishedCrawling()
    {

    }
};


Crawler::create()
    ->setCrawlObserver($observer)
    ->setCrawlProfile(new CrawlInternalUrls('https://laravel.com'))
    ->startCrawling('https://laravel.com');