<?php

namespace Spatie\Crawler\Concerns;

use Spatie\Crawler\CrawlQueues\CrawlQueue;
use Spatie\Crawler\CrawlUrl;

trait HasCrawlQueue
{
    protected CrawlQueue $crawlQueue;

    public function crawlQueue(CrawlQueue $crawlQueue): self
    {
        $this->crawlQueue = $crawlQueue;

        return $this;
    }

    public function getCrawlQueue(): CrawlQueue
    {
        return $this->crawlQueue;
    }

    public function addToCrawlQueue(CrawlUrl $crawlUrl): self
    {
        if ($this->getCrawlQueue()->has($crawlUrl->url)) {
            return $this;
        }

        if ($this->matchesAlwaysCrawl($crawlUrl->url)) {
            $this->crawlQueue->add($crawlUrl);

            return $this;
        }

        if ($this->matchesNeverCrawl($crawlUrl->url)) {
            return $this;
        }

        if ($this->respectRobots && $this->robotsTxt !== null) {
            if (! $this->robotsTxt->allows($crawlUrl->url, $this->getUserAgent())) {
                return $this;
            }
        }

        if (! $this->getCrawlProfile()->shouldCrawl($crawlUrl->url)) {
            return $this;
        }

        $this->crawlQueue->add($crawlUrl);

        return $this;
    }
}
