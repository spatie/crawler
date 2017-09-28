<?php

namespace Spatie\Crawler;


use LayerShifter\TLDExtract\Extract;

class CrawlInternalWithSubdomainUrls implements CrawlProfile
{

    protected $host = '';

    /** @var Extract */
    protected $extract;

    public function __construct(string $baseUrl)
    {
        $this->host = parse_url($baseUrl, PHP_URL_HOST);
        $this->extract = new Extract();
    }

    public function shouldCrawl(Url $url): bool
    {
        $baseUrl = $this->extract->parse($this->host);
        $currentUrl = $this->extract->parse($url);

        return $baseUrl->getHostname() === $currentUrl->getHostname();
    }
}
