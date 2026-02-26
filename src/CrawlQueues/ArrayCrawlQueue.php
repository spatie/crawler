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
        $urlString = $crawlUrl->url;

        if (! isset($this->urls[$urlString])) {
            $crawlUrl->id = $urlString;

            $this->urls[$urlString] = $crawlUrl;
            $this->pendingUrls[$urlString] = $crawlUrl;
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
        $urlString = $crawlUrl->url;

        if (isset($this->pendingUrls[$urlString])) {
            return false;
        }

        if (isset($this->urls[$urlString])) {
            return true;
        }

        return false;
    }

    public function markAsProcessed(CrawlUrl $crawlUrl): void
    {
        $urlString = $crawlUrl->url;

        unset($this->pendingUrls[$urlString]);
    }

    public function getProcessedUrlCount(): int
    {
        return count($this->urls) - count($this->pendingUrls);
    }

    public function has(string $url): bool
    {
        return isset($this->urls[$url]);
    }

    public function getPendingUrl(): ?CrawlUrl
    {
        foreach ($this->pendingUrls as $pendingUrl) {
            return $pendingUrl;
        }

        return null;
    }
}
