<?php

namespace Spatie\Crawler\CrawlQueues;

use Spatie\Crawler\CrawlUrl;

interface CrawlQueue
{
    public function add(CrawlUrl $url): self;

    public function has($crawlUrl): bool;

    public function hasPendingUrls(): bool;

    public function getUrlById($id): CrawlUrl;

    public function getPendingUrl(): ?CrawlUrl;

    public function hasAlreadyBeenProcessed(CrawlUrl $url): bool;

    public function markAsProcessed(CrawlUrl $crawlUrl): void;

    public function getProcessedUrlCount(): int;
}
