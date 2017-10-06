<?php

namespace Spatie\Crawler;

class CrawlInternalWithSubdomainUrls implements CrawlProfile
{
    protected $host = '';

    public function __construct(string $baseUrl)
    {
        $this->host = parse_url($baseUrl, PHP_URL_HOST);
    }

    public function shouldCrawl(Url $url): bool
    {
        return $this->isSubdomainOfHost($url);
    }

    public function isSubdomainOfHost(Url $url)
    {
        return substr($url->host, -strlen($this->host)) === $this->host;
    }
}
