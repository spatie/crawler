<?php

namespace Spatie\Crawler\CrawlQueue;

use Spatie\Crawler\CrawlUrl;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\Exception\InvalidUrl;
use Spatie\Crawler\Exception\UrlNotFoundByIndex;

class ArrayCrawlQueue implements CrawlQueue
{
    /**
     * Pending URLs, indexed by URL string.
     *
     * @var CrawlUrl[]
     */
    protected $pendingUrls = [];

    /**
     * Processed URLs, indexed by URL string.
     *
     * @var CrawlUrl[]
     */
    protected $processedUrls = [];

    public function add(CrawlUrl $url) : CrawlQueue
    {
        $urlString = (string) $url->url;

        if (! isset($this->pendingUrls[$urlString]) && ! isset($this->processedUrls[$urlString])) {
            $url->setId($urlString);
            $this->pendingUrls[$urlString] = $url;
        }

        return $this;
    }

    public function hasPendingUrls() : bool
    {
        return (bool) $this->pendingUrls;
    }

    public function getUrlById($id) : CrawlUrl
    {
        if (isset($this->pendingUrls[$id])) {
            return $this->pendingUrls[$id];
        }

        if (isset($this->processedUrls[$id])) {
            return $this->processedUrls[$id];
        }

        throw new UrlNotFoundByIndex("Crawl url {$id} not found in collection.");
    }

    public function hasAlreadyBeenProcessed(CrawlUrl $url) : bool
    {
        $url = (string) $url->url;

        return isset($this->processedUrls[$url]);
    }

    public function markAsProcessed(CrawlUrl $crawlUrl)
    {
        $url = (string) $crawlUrl->url;

        $this->processedUrls[$url] = $crawlUrl;
        unset($this->pendingUrls[$url]);
    }

    /**
     * @param CrawlUrl|UriInterface $crawlUrl
     *
     * @return bool
     */
    public function has($crawlUrl) : bool
    {
        if ($crawlUrl instanceof CrawlUrl) {
            $url = (string) $crawlUrl->url;
        } elseif ($crawlUrl instanceof UriInterface) {
            $url = (string) $crawlUrl;
        } else {
            throw InvalidUrl::unexpectedType($crawlUrl);
        }

        return isset($this->pendingUrls[$url]) || isset($this->processedUrls[$url]);
    }

    public function getFirstPendingUrl() : ?CrawlUrl
    {
        foreach ($this->pendingUrls as $pendingUrl) {
            return $pendingUrl;
        }

        return null;
    }
}
