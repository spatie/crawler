<?php

namespace Spatie\Crawler\CrawlProfiles;

class CrawlInternalUrls implements CrawlProfile
{
    protected string $baseHost;

    public function __construct(string $baseUrl)
    {
        $this->baseHost = parse_url($baseUrl, PHP_URL_HOST) ?? $baseUrl;
    }

    public function shouldCrawl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $this->baseHost === $host;
    }
}
