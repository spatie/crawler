<?php

namespace Spatie\Crawler\CrawlProfiles;

use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlProfiles\CrawlProfile;

class CrawlAllUrls extends CrawlProfile
{
    public function shouldCrawl(UriInterface $url): bool
    {
        return true;
    }
}
