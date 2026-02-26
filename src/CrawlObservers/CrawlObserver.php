<?php

namespace Spatie\Crawler\CrawlObservers;

use GuzzleHttp\Exception\RequestException;
use Spatie\Crawler\CrawlResponse;

abstract class CrawlObserver
{
    public function willCrawl(string $url, ?string $linkText): void {}

    public function crawled(
        string $url,
        CrawlResponse $response,
        ?string $foundOnUrl = null,
        ?string $linkText = null,
    ): void {}

    public function crawlFailed(
        string $url,
        RequestException $requestException,
        ?string $foundOnUrl = null,
        ?string $linkText = null,
    ): void {}

    public function finishedCrawling(): void {}
}
