<?php

namespace Spatie\Crawler\CrawlProfiles;

class CrawlSubdomains implements CrawlProfile
{
    protected string $baseHost;

    public function __construct(string $baseUrl)
    {
        $this->baseHost = parse_url($baseUrl, PHP_URL_HOST) ?? $baseUrl;
    }

    public function shouldCrawl(string $url): bool
    {
        return $this->isSubdomainOfHost($url);
    }

    public function isSubdomainOfHost(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if ($host === null) {
            return false;
        }

        return str_ends_with($host, $this->baseHost);
    }
}
