<?php

namespace Spatie\Crawler;

interface CrawlProfile
{
    /**
     * Determine if the given url should be crawled.
     *
     * @param \Spatie\Crawler\Url $url
     *
     * @return bool
     */
    public function shouldCrawl(Url $url): bool;
}
