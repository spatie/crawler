<?php

namespace Spatie\Crawler;

class CrawlAllUrls implements CrawlProfile
{
    /*
     * Determine if the given url should be crawled.
     */
    public function shouldCrawl(Url $url): bool
    {
        return true;
    }
}
