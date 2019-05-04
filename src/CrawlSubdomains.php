<?php

namespace Spatie\Crawler;

use Psr\Http\Message\UriInterface;

class CrawlSubdomains extends CrawlProfile
{
    public function shouldCrawl(UriInterface $url): bool
    {
        return $this->isSubdomainOfHost($url);
    }

    public function isSubdomainOfHost(UriInterface $url): bool
    {
        return substr($url->getHost(), -strlen($this->baseUrl->getHost())) === $this->baseUrl->getHost();
    }
}
