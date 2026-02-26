<?php

namespace Spatie\Crawler\Concerns;

use Closure;
use Spatie\Crawler\CrawlObservers\CrawlObserver;
use Spatie\Crawler\CrawlObservers\CrawlObserverCollection;

trait HasCrawlObservers
{
    protected CrawlObserverCollection $crawlObservers;

    public function onWillCrawl(Closure $callback): self
    {
        $this->crawlObservers->onWillCrawl($callback);

        return $this;
    }

    public function onCrawled(Closure $callback): self
    {
        $this->crawlObservers->onCrawled($callback);

        return $this;
    }

    public function onFailed(Closure $callback): self
    {
        $this->crawlObservers->onFailed($callback);

        return $this;
    }

    public function onFinished(Closure $callback): self
    {
        $this->crawlObservers->onFinished($callback);

        return $this;
    }

    public function addObserver(CrawlObserver $observer): self
    {
        $this->crawlObservers->addObserver($observer);

        return $this;
    }

    public function getCrawlObservers(): CrawlObserverCollection
    {
        return $this->crawlObservers;
    }
}
