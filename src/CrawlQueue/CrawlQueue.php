<?php

namespace Spatie\Crawler\CrawlQueue;

use Spatie\Crawler\CrawlUrl;

interface CrawlQueue
{
    public function add(CrawlUrl $url): self;

    public function has($crawlUrl): bool;

    public function hasPendingUrls(): bool;

    public function getUrlById($id): CrawlUrl;

    /** @return \Spatie\Crawler\CrawlUrl|null */
    public function getFirstPendingUrl();

    public function hasAlreadyBeenProcessed(CrawlUrl $url): bool;

    public function markAsProcessed(CrawlUrl $crawlUrl);
}
