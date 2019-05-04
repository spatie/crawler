<?php

namespace Spatie\Crawler;

use Psr\Http\Message\UriInterface;

class CrawlInternalUrls extends CrawlProfile
{
    public function shouldCrawl(UriInterface $url): bool
    {
        return $this->baseUrl->getHost() === $url->getHost();
    }
}
