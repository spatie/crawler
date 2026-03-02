<?php

namespace Spatie\Crawler\CrawlProfiles;

class CrawlInternalUrls implements CrawlProfile
{
    protected string $baseHost;

    public function __construct(
        string $baseUrl,
        protected bool $matchWww = false,
    ) {
        $this->baseHost = parse_url($baseUrl, PHP_URL_HOST) ?? $baseUrl;
    }

    public function shouldCrawl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if ($this->baseHost === $host) {
            return true;
        }

        if (! $this->matchWww) {
            return false;
        }

        return $this->stripWww($this->baseHost) === $this->stripWww($host ?? '');
    }

    protected function stripWww(string $host): string
    {
        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }
}
