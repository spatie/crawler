<?php

namespace Spatie\Crawler;

use Psr\Http\Message\UriInterface;

abstract class CrawlProfile
{
    /**
     * Determines the xpath to be crawled
     * @param string $xpath
     */
    public $xpath = '//a';

    /**
     * Determine if the given url should be crawled.
     *
     * @param \Psr\Http\Message\UriInterface $url
     *
     * @return bool
     */
    abstract public function shouldCrawl(UriInterface $url): bool;
}
