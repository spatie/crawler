<?php

namespace Spatie\Crawler;

class CrawlInternalUrls implements CrawlProfile
{
    protected $host = '';

    public function __construct(string $baseUrl)
    {
        $this->host = parse_url($baseUrl, PHP_URL_HOST);
    }

    public function shouldCrawl(Url $url): bool
    {
        return $this->host === $url->host;
    }
}
