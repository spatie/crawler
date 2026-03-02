<?php

namespace Spatie\Crawler\CrawlProfiles;

interface CrawlProfile
{
    public function shouldCrawl(string $url): bool;
}
