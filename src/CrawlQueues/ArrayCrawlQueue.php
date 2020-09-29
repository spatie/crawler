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

    public function add(CrawlUrl $url): CrawlQueue
    {
        $urlString = (string) $url->url;

        if (! isset($this->urls[$urlString])) {
            $url->setId($urlString);

            $this->urls[$urlString] = $url;
            $this->pendingUrls[$urlString] = $url;
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

    public function hasAlreadyBeenProcessed(CrawlUrl $url): bool
    {
        $url = (string) $url->url;

        if (isset($this->pendingUrls[$url])) {
            return false;
        }

        if (isset($this->urls[$url])) {
            return true;
        }

        return false;
    }

    public function markAsProcessed(CrawlUrl $crawlUrl): void
    {
        $url = (string) $crawlUrl->url;

        unset($this->pendingUrls[$url]);
    }

    /**
     * @param CrawlUrl|UriInterface $crawlUrl
     *
     * @return bool
     */
    public function has($crawlUrl): bool
    {
        if ($crawlUrl instanceof CrawlUrl) {
            $url = (string) $crawlUrl->url;
        } elseif ($crawlUrl instanceof UriInterface) {
            $url = (string) $crawlUrl;
        } else {
            throw InvalidUrl::unexpectedType($crawlUrl);
        }

        return isset($this->urls[$url]);
    }

    public function getFirstPendingUrl(): ?CrawlUrl
    {
        foreach ($this->pendingUrls as $pendingUrl) {
            return $pendingUrl;
        }

        return null;
    }
}
