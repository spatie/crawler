<?php

namespace Spatie\Crawler\CrawlProfiles;

class CrawlAllUrls implements CrawlProfile
{
    public function shouldCrawl(string $url): bool
    {
        return true;
    }
}
