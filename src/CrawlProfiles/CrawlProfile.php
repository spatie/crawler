<?php

namespace Spatie\Crawler\CrawlProfiles;

use Psr\Http\Message\UriInterface;

abstract class CrawlProfile
{
    /**
     * Determine if the given url should be crawled.
     *
     * @param \Psr\Http\Message\UriInterface $url
     *
     * @return bool
     */
    abstract public function shouldCrawl(UriInterface $url): bool;
}
