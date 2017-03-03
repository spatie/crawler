<?php

namespace Spatie\Crawler;

use Psr\Http\Message\UriInterface;

interface CrawlProfile
{
    /**
     * Determine if the given url should be crawled.
     *
     * @param UriInterface $url
     *
     * @return bool
     */
    public function shouldCrawl(UriInterface $url): bool;
}
