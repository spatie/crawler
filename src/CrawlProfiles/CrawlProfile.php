<?php

namespace Spatie\Crawler\CrawlProfiles;

use Psr\Http\Message\UriInterface;

abstract class CrawlProfile
{
    abstract public function shouldCrawl(UriInterface $url): bool;
}
