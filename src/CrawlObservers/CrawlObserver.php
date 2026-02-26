<?php

namespace Spatie\Crawler\CrawlObservers;

use GuzzleHttp\Exception\RequestException;
use Spatie\Crawler\CrawlResponse;
use Spatie\Crawler\Enums\ResourceType;

abstract class CrawlObserver
{
    public function willCrawl(string $url, ?string $linkText, ?ResourceType $resourceType = null): void {}

    public function crawled(
        string $url,
        CrawlResponse $response,
        ?string $foundOnUrl = null,
        ?string $linkText = null,
        ?ResourceType $resourceType = null,
    ): void {}

    public function crawlFailed(
        string $url,
        RequestException $requestException,
        ?string $foundOnUrl = null,
        ?string $linkText = null,
        ?ResourceType $resourceType = null,
    ): void {}

    public function finishedCrawling(): void {}
}
