<?php

namespace Spatie\Crawler;

class CrawlAllUrls implements CrawlProfile
{
    /**
     * Determine if the given url should be crawled.
     *
     * @param \Spatie\Crawler\Url $url
     *
     * @return bool
     */
    public function shouldCrawl(Url $url)
    {
        return true;
    }
}