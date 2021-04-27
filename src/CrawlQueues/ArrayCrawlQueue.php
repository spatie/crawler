<?php

namespace Spatie\Crawler\CrawlQueues;

use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlUrl;
use Spatie\Crawler\Exceptions\InvalidUrl;
use Spatie\Crawler\Exceptions\UrlNotFoundByIndex;

class ArrayCrawlQueue implements CrawlQueue
{
    /**
     * All known URLs, indexed by URL string.
     *
     * @var CrawlUrl[]
     */
    protected array $urls = [];

    /**
     * Pending URLs, indexed by URL string.
     *
     * @var CrawlUrl[]
     */
    protected array $pendingUrls = [];

    public function add(CrawlUrl $crawlUrl): CrawlQueue
    {
        $urlString = (string) $crawlUrl->url;

        if (! isset($this->urls[$urlString])) {
            $crawlUrl->setId($urlString);

            $this->urls[$urlString] = $crawlUrl;
            $this->pendingUrls[$urlString] = $crawlUrl;
        }

        return $this;
    }

    public function hasPendingUrls(): bool
    {
        return (bool) $this->pendingUrls;
    }

    public function getUrlById($id): CrawlUrl
    {
        if (! isset($this->urls[$id])) {
            throw new UrlNotFoundByIndex("Crawl url {$id} not found in collection.");
        }

        return $this->urls[$id];
    }

    public function hasAlreadyBeenProcessed(CrawlUrl $crawlUrl): bool
    {
        $urlString = (string) $crawlUrl->url;

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
        $urlString = (string) $crawlUrl->url;

        unset($this->pendingUrls[$urlString]);
    }

    public function getProcessedUrlCount(): int
    {
        return count($this->urls) - count($this->pendingUrls);
    }

    public function has(CrawlUrl | UriInterface $crawlUrl): bool
    {
        if ($crawlUrl instanceof CrawlUrl) {
            $urlString = (string) $crawlUrl->url;
        } elseif ($crawlUrl instanceof UriInterface) {
            $urlString = (string) $crawlUrl;
        } else {
            throw InvalidUrl::unexpectedType($crawlUrl);
        }

        return isset($this->urls[$urlString]);
    }

    public function getPendingUrl(): ?CrawlUrl
    {
        foreach ($this->pendingUrls as $pendingUrl) {
            return $pendingUrl;
        }

        return null;
    }
}
