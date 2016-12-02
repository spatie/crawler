<?php

namespace Spatie\Crawler;

use Illuminate\Support\Collection;

class CrawlQueue
{
    /** @var \Illuminate\Support\Collection */
    protected $pending;

    /** @var \Illuminate\Support\Collection */
    protected $processing;

    /** @var \Illuminate\Support\Collection */
    protected $processed;

    public function hasPendingUrls(): bool
    {
        return count($this->pending);
    }

    public function getPendingUrls(): Collection
    {
        $this->pending->values();
    }

    /**
     * @param $index
     * @return CrawlUrl|null
     */
    public function getPendingUrlAtIndex(int $index)
    {
        return $this->getPendingUrls()[$index];
    }

    public function isBeingProcessed(CrawlUrl $url)
    {
        return $this->contains($this->processing, $url);
    }

    public function hasAlreadyBeenProcessed(CrawlUrl $url)
    {
        return $this->contains($this->processed, $url);
    }

    public function moveToProcessing(CrawlUrl $crawlUrl)
    {
        $this->move($crawlUrl, 'pending', 'processing');
    }

    public function moveToProcessed(CrawlUrl $crawlUrl)
    {
        $this->move($crawlUrl, 'processing', 'processed');
    }

    public function add(CrawlUrl $url)
    {
        if ($this->has($url)) {
            return;
        }

        $this->pending->push($url);
    }

    public function has(CrawlUrl $url): bool
    {
        if ($this->contains($this->pending, $url)) {
            return true;
        }

        if ($this->contains($this->processing, $url)) {
            return true;
        }

        if ($this->contains($this->processed, $url)) {
            return true;
        }

        return false;
    }

    protected function move(CrawlUrl $searchCrawlUrl, string $sourceName, string $destinationName)
    {
        $this->{$sourceName} = $this->{$sourceName}->reject(function (CrawlUrl $crawlUrl) use ($searchCrawlUrl) {
            return $crawlUrl->url->isEqual($searchCrawlUrl->url);
        });

        $this->{$destinationName}->push($searchCrawlUrl);
    }

    protected function contains($collection, CrawlUrl $searchCrawlUrl)
    {
        foreach ($collection as $crawlUrl) {
            if ($crawlUrl->isEqual($searchCrawlUrl->url)) {
                return true;
            }
        }

        return false;
    }
}
