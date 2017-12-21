<?php

namespace Spatie\Crawler;

use Psr\Http\Message\UriInterface;

class CrawlAllUrls implements CrawlProfile
{
    public function shouldCrawl(UriInterface $url): bool
    {
        return true;
    }
}
