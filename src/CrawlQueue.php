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

    public function hasPendingUrls(): bool
    {
        return count($this->pending);
    }

    public function getPendingUrls(): Collection
    {
        return $this->pending->values();
    }

    /**
     * @param $index
     * @return CrawlUrl|null
     */
    public function getPendingUrlAtIndex(int $index)
    {
        if (!isset($this->getPendingUrls()[$index])) {
            return null;
        }

        return $this->getPendingUrls()[$index];
    }

    public function hasAlreadyBeenProcessed(CrawlUrl $url)
    {
        return $this->contains($this->processed, $url);
    }

    public function moveToProcessed(CrawlUrl $crawlUrl)
    {
        $this->processed->push($crawlUrl);
    }

    public function add(CrawlUrl $url)
    {
        if ($this->has($url)) {
            return;
        }

        $this->pending->push($url);
    }

    /**
     * @param CrawlUrl|Url $crawlUrl
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

    protected function contains(Collection $collection, CrawlUrl $searchCrawlUrl)
    {
        foreach ($collection as $crawlUrl) {
            if ($crawlUrl->url->isEqual($searchCrawlUrl->url)) {
                return true;
            }
        }

        return false;
    }

    public function cleanUpPending()
    {
        $this->pending = $this->pending
            ->reject(function (CrawlUrl $crawlUrl) {
                return $this->contains($this->processed, $crawlUrl);
            })
            ->values();
    }
}