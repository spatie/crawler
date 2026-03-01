<?php

namespace Spatie\Crawler\Concerns;

use Closure;
use Spatie\Crawler\CrawlProfiles\ClosureCrawlProfile;
use Spatie\Crawler\CrawlProfiles\CrawlAllUrls;
use Spatie\Crawler\CrawlProfiles\CrawlInternalUrls;
use Spatie\Crawler\CrawlProfiles\CrawlProfile;
use Spatie\Crawler\CrawlProfiles\CrawlSubdomains;

trait HasCrawlScope
{
    protected ?CrawlProfile $crawlProfile = null;

    protected ?string $scopeMode = null;

    protected array $alwaysCrawlPatterns = [];

    protected array $neverCrawlPatterns = [];

    public function internalOnly(): self
    {
        $this->scopeMode = 'internal';
        $this->crawlProfile = null;

        return $this;
    }

    public function includeSubdomains(): self
    {
        $this->scopeMode = 'subdomains';
        $this->crawlProfile = null;

        return $this;
    }

    public function shouldCrawl(Closure $closure): self
    {
        $this->crawlProfile = new ClosureCrawlProfile($closure);
        $this->scopeMode = null;

        return $this;
    }

    public function crawlProfile(CrawlProfile $crawlProfile): self
    {
        $this->crawlProfile = $crawlProfile;
        $this->scopeMode = null;

        return $this;
    }

    public function alwaysCrawl(array $patterns): self
    {
        $this->alwaysCrawlPatterns = array_merge($this->alwaysCrawlPatterns, $patterns);

        return $this;
    }

    public function neverCrawl(array $patterns): self
    {
        $this->neverCrawlPatterns = array_merge($this->neverCrawlPatterns, $patterns);

        return $this;
    }

    public function matchesAlwaysCrawl(string $url): bool
    {
        foreach ($this->alwaysCrawlPatterns as $pattern) {
            if (fnmatch($pattern, $url)) {
                return true;
            }
        }

        return false;
    }

    public function matchesNeverCrawl(string $url): bool
    {
        foreach ($this->neverCrawlPatterns as $pattern) {
            if (fnmatch($pattern, $url)) {
                return true;
            }
        }

        return false;
    }

    public function getCrawlProfile(): CrawlProfile
    {
        return $this->crawlProfile ?? new CrawlAllUrls;
    }

    protected function resolveScope(): void
    {
        if ($this->crawlProfile !== null) {
            return;
        }

        $this->crawlProfile = match ($this->scopeMode) {
            'internal' => new CrawlInternalUrls($this->baseUrl),
            'subdomains' => new CrawlSubdomains($this->baseUrl),
            default => new CrawlAllUrls,
        };
    }
}
