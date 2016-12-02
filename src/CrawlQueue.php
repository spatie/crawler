<?php

namespace Spatie\Crawler;

use Illuminate\Support\Collection;

class CrawlQueue
{
    /** @var \Illuminate\Support\Collection */
    public $pending;

    /** @var \Illuminate\Support\Collection */
    public $processed;

    public function __construct()
    {
        $this->pending = collect();

        $this->processed = collect();
    }

    public function add(CrawlUrl $url)
    {
        if ($this->has($url)) {
            return;
        }

        $this->pending->push($url);

        return $this;
    }

    public function hasPendingUrls(): bool
    {
        return count($this->pending);
    }

    public function getPendingUrls(): Collection
    {
        return $this->pending->values();
    }

    /**
     * @param int $index
     *
     * @return \Spatie\Crawler\CrawlUrl|null
     */
    public function getPendingUrlAtIndex(int $index)
    {
        if (! isset($this->getPendingUrls()[$index])) {
            return;
        }

        return $this->getPendingUrls()[$index];
    }

    public function hasAlreadyBeenProcessed(CrawlUrl $url)
    {
        return $this->contains($this->processed, $url);
    }

    public function markAsProcessed(CrawlUrl $crawlUrl)
    {
        $this->processed->push($crawlUrl);
    }

    /**
     * @param CrawlUrl|Url $crawlUrl
     *
     * @return bool
     */
    public function has($crawlUrl): bool
    {
        if ($crawlUrl instanceof Url) {
            $crawlUrl = CrawlUrl::create($crawlUrl);
        }

        if ($this->contains($this->pending, $crawlUrl)) {
            return true;
        }

        if ($this->contains($this->processed, $crawlUrl)) {
            return true;
        }

        return false;
    }

    public function removeProcessedUrlsFromPending()
    {
        $this->pending = $this->pending
            ->reject(function (CrawlUrl $crawlUrl) {
                return $this->contains($this->processed, $crawlUrl);
            })
            ->values();
    }

    protected function contains(Collection $collection, CrawlUrl $searchCrawlUrl): bool
    {
        foreach ($collection as $crawlUrl) {
            if ($crawlUrl->url->isEqual($searchCrawlUrl->url)) {
                return true;
            }
        }

        return false;
    }
}
