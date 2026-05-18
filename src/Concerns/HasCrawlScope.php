<?php

namespace Spatie\Crawler\Concerns;

use Closure;
use Spatie\Crawler\CrawlProfiles\ClosureCrawlProfile;
use Spatie\Crawler\CrawlProfiles\CrawlAllUrls;
use Spatie\Crawler\CrawlProfiles\CrawlInternalUrls;
use Spatie\Crawler\CrawlProfiles\CrawlProfile;

trait HasCrawlScope
{
    /**
     * fnmatch() rejects strings longer than the platform's FILENAME_MAX with a
     * warning (4096 on Linux, 1024 on macOS/BSD). We use the lowest common
     * value so behavior is consistent across platforms; real URLs almost never
     * exceed this anyway.
     */
    protected int $fnmatchMaxLength = 1024;

    protected ?CrawlProfile $crawlProfile = null;

    protected ?string $scopeMode = null;

    protected bool $matchWww = false;

    protected bool $includeSubdomains = false;

    protected array $alwaysCrawlPatterns = [];

    protected array $neverCrawlPatterns = [];

    public function internalOnly(): self
    {
        $this->scopeMode = 'internal';
        $this->crawlProfile = null;

        return $this;
    }

    public function matchWww(): self
    {
        $this->matchWww = true;

        return $this;
    }

    public function includeSubdomains(): self
    {
        $this->includeSubdomains = true;

        if ($this->scopeMode === null) {
            $this->scopeMode = 'internal';
            $this->crawlProfile = null;
        }

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
        if (strlen($url) > $this->fnmatchMaxLength) {
            return false;
        }

        foreach ($this->alwaysCrawlPatterns as $pattern) {
            if (fnmatch($pattern, $url)) {
                return true;
            }
        }

        return false;
    }

    public function matchesNeverCrawl(string $url): bool
    {
        if (strlen($url) > $this->fnmatchMaxLength) {
            return false;
        }

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
            'internal' => new CrawlInternalUrls($this->baseUrl, $this->matchWww, $this->includeSubdomains),
            default => new CrawlAllUrls,
        };
    }
}
