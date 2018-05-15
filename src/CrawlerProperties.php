<?php

namespace Spatie\Crawler;

use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlQueue\CrawlQueue;

trait CrawlerProperties
{
    public function setConcurrency(int $concurrency): self
    {
        $this->concurrency = $concurrency;

        return $this;
    }

    public function setMaximumResponseSize(int $maximumResponseSizeInBytes): self
    {
        $this->maximumResponseSize = $maximumResponseSizeInBytes;

        return $this;
    }

    public function setMaximumCrawlCount(int $maximumCrawlCount): self
    {
        $this->maximumCrawlCount = $maximumCrawlCount;

        return $this;
    }

    public function setMaximumDepth(int $maximumDepth): self
    {
        $this->maximumDepth = $maximumDepth;

        return $this;
    }

    public function ignoreRobots(): self
    {
        $this->respectRobots = false;

        return $this;
    }

    public function respectRobots(): self
    {
        $this->respectRobots = true;

        return $this;
    }

    public function setCrawlQueue(CrawlQueue $crawlQueue): self
    {
        $this->crawlQueue = $crawlQueue;

        return $this;
    }

    public function executeJavaScript(): self
    {
        $this->executeJavaScript = true;

        return $this;
    }

    public function doNotExecuteJavaScript(): self
    {
        $this->executeJavaScript = false;

        return $this;
    }

    /**
     * @param \Spatie\Crawler\CrawlObserver|array[\Spatie\Crawler\CrawlObserver] $crawlObservers
     *
     * @return $this
     */
    public function setCrawlObserver($crawlObservers)
    {
        if (! is_array($crawlObservers)) {
            $crawlObservers = [$crawlObservers];
        }

        return $this->setCrawlObservers($crawlObservers);
    }

    public function setCrawlObservers(array $crawlObservers): self
    {
        $this->crawlObservers = $crawlObservers;

        return $this;
    }

    public function addCrawlObserver(CrawlObserver $crawlObserver): self
    {
        $this->crawlObservers[] = $crawlObserver;

        return $this;
    }

    public function setCrawlProfile(CrawlProfile $crawlProfile): self
    {
        $this->crawlProfile = $crawlProfile;

        return $this;
    }

    public function getBaseUrl(): UriInterface
    {
        return $this->baseUrl;
    }

    public function getCrawlQueue(): CrawlQueue
    {
        return $this->crawlQueue;
    }

    public function getCrawlProfile(): CrawlProfile
    {
        return $this->crawlProfile;
    }

    /**
     * @return \Spatie\Crawler\CrawlObserver[]
     */
    public function getCrawlObservers(): array
    {
        return $this->crawlObservers;
    }

    public function getMaximumResponseSize(): ?int
    {
        return $this->maximumResponseSize;
    }

    public function mustRespectRobots(): bool
    {
        return $this->respectRobots;
    }
}
