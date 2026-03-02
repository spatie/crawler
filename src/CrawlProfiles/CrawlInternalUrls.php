<?php

namespace Spatie\Crawler\CrawlProfiles;

class CrawlInternalUrls implements CrawlProfile
{
    protected string $baseHost;

    public function __construct(
        string $baseUrl,
        protected bool $matchWww = false,
        protected bool $includeSubdomains = false,
    ) {
        $this->baseHost = parse_url($baseUrl, PHP_URL_HOST) ?? $baseUrl;
    }

    public function shouldCrawl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if ($this->baseHost === $host) {
            return true;
        }

        if (! $this->matchWww && ! $this->includeSubdomains) {
            return false;
        }

        $strippedBase = $this->stripWww($this->baseHost);
        $strippedHost = $this->stripWww($host ?? '');

        if ($this->matchWww && $strippedBase === $strippedHost) {
            return true;
        }

        if ($this->includeSubdomains && str_ends_with($strippedHost, ".{$strippedBase}")) {
            return true;
        }

        return false;
    }

    protected function stripWww(string $host): string
    {
        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }
}
