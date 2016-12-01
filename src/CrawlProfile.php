<?php

namespace Spatie\Crawler;

interface CrawlProfile
{
    /*
     * Determine if the given url should be crawled.
     */
    public function shouldCrawl(Url $url): bool;
}
