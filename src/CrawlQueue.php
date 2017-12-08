<?php

namespace Spatie\Crawler;

interface CrawlQueue
{
    public function add(CrawlUrl $url): self;

    public function has($crawlUrl): bool;

    public function hasPendingUrls(): bool;

    public function getUrlById(int $id): CrawlUrl;

    /** @return \Spatie\Crawler\CrawlUrl|null */
    public function getFirstPendingUrl();

    public function hasAlreadyBeenProcessed(CrawlUrl $url);

    public function markAsProcessed(CrawlUrl $crawlUrl);
}
