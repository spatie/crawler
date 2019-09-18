<?php

namespace Spatie\Crawler\CrawlQueue;

use Spatie\Crawler\CrawlUrl;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\Exception\InvalidUrl;
use Spatie\Crawler\Exception\UrlNotFoundByIndex;

class ArrayCrawlQueue implements CrawlQueue
{
    /**
     * All known URLs, indexed by URL string.
     *
     * @var CrawlUrl[]
     */
    protected $urls = [];

    /**
     * Pending URLs, indexed by URL string.
     *
     * @var CrawlUrl[]
     */
    protected $pendingUrls = [];

    public function add(CrawlUrl $url) : CrawlQueue
    {
        $id = (string) $url->getId();

        if (! isset($this->urls[$id])) {
            $url->setId($id);

            $this->urls[$id] = $url;
            $this->pendingUrls[$id] = $url;
        }

        return $this;
    }

    public function hasPendingUrls() : bool
    {
        return (bool) $this->pendingUrls;
    }

    public function getUrlById($id) : CrawlUrl
    {
        if (! isset($this->urls[$id])) {
            throw new UrlNotFoundByIndex("Crawl url {$id} not found in collection.");
        }

        return $this->urls[$id];
    }

    public function hasAlreadyBeenProcessed(CrawlUrl $url) : bool
    {
        $id = (string) $url->getId();

        if (isset($this->pendingUrls[$id])) {
            return false;
        }

        if (isset($this->urls[$id])) {
            return true;
        }

        return false;
    }

    public function markAsProcessed(CrawlUrl $crawlUrl)
    {
        $id = (string) $crawlUrl->getId();

        unset($this->pendingUrls[$id]);
    }

    /**
     * @param CrawlUrl|UriInterface $crawlUrl
     *
     * @return bool
     */
    public function has($crawlUrl) : bool
    {
        if ($crawlUrl instanceof CrawlUrl) {
            $url = (string) $crawlUrl->getId();
        } elseif ($crawlUrl instanceof UriInterface) {
            $url = (string) $crawlUrl;
        } else {
            throw InvalidUrl::unexpectedType($crawlUrl);
        }

        return isset($this->urls[$url]);
    }

    public function getFirstPendingUrl() : ?CrawlUrl
    {
        foreach ($this->pendingUrls as $pendingUrl) {
            return $pendingUrl;
        }

        return null;
    }
}
