<?php

namespace Spatie\Crawler\CrawlQueues;

use Spatie\Crawler\CrawlUrl;
use Spatie\Crawler\Exceptions\UrlNotFoundByIndex;

class ArrayCrawlQueue implements CrawlQueue
{
    /** @var CrawlUrl[] */
    protected array $urls = [];

    /** @var CrawlUrl[] */
    protected array $pendingUrls = [];

    public function add(CrawlUrl $crawlUrl): CrawlQueue
    {
        $normalizedUrl = $this->normalizeUrl($crawlUrl->url);

        if (! isset($this->urls[$normalizedUrl])) {
            $crawlUrl->id = $normalizedUrl;

            $this->urls[$normalizedUrl] = $crawlUrl;
            $this->pendingUrls[$normalizedUrl] = $crawlUrl;
        }

        return $this;
    }

    public function hasPendingUrls(): bool
    {
        return (bool) $this->pendingUrls;
    }

    public function getUrlById(mixed $id): CrawlUrl
    {
        if (! isset($this->urls[$id])) {
            throw new UrlNotFoundByIndex("Crawl url {$id} not found in collection.");
        }

        return $this->urls[$id];
    }

    public function hasAlreadyBeenProcessed(CrawlUrl $crawlUrl): bool
    {
        $normalizedUrl = $this->normalizeUrl($crawlUrl->url);

        if (isset($this->pendingUrls[$normalizedUrl])) {
            return false;
        }

        if (isset($this->urls[$normalizedUrl])) {
            return true;
        }

        return false;
    }

    public function markAsProcessed(CrawlUrl $crawlUrl): void
    {
        $normalizedUrl = $this->normalizeUrl($crawlUrl->url);

        unset($this->pendingUrls[$normalizedUrl]);
    }

    public function getProcessedUrlCount(): int
    {
        return count($this->urls) - count($this->pendingUrls);
    }

    public function has(string $url): bool
    {
        return isset($this->urls[$this->normalizeUrl($url)]);
    }

    public function getPendingUrl(): ?CrawlUrl
    {
        foreach ($this->pendingUrls as $pendingUrl) {
            return $pendingUrl;
        }

        return null;
    }

    public function getUrlCount(): int
    {
        return count($this->urls);
    }

    public function getPendingUrlCount(): int
    {
        return count($this->pendingUrls);
    }

    protected function normalizeUrl(string $url): string
    {
        $parsed = parse_url($url);

        if ($parsed === false || ! isset($parsed['host'])) {
            return $url;
        }

        $scheme = strtolower($parsed['scheme'] ?? 'https');
        $host = strtolower($parsed['host']);

        $port = $parsed['port'] ?? null;
        if (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443)) {
            $port = null;
        }

        $path = $parsed['path'] ?? '/';
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        $query = $parsed['query'] ?? null;
        if ($query === '') {
            $query = null;
        }

        $normalized = $scheme.'://'.$host;

        if ($port !== null) {
            $normalized .= ':'.$port;
        }

        $normalized .= $path;

        if ($query !== null) {
            $normalized .= '?'.$query;
        }

        return $normalized;
    }
}
