<?php

namespace Spatie\Crawler\Test\TestClasses;

use GuzzleHttp\Exception\RequestException;
use Spatie\Crawler\CrawlObservers\CrawlObserver;
use Spatie\Crawler\CrawlResponse;
use Spatie\Crawler\Enums\ResourceType;

class CrawlLogger extends CrawlObserver
{
    protected string $observerId;

    public function __construct(string $observerId = '')
    {
        if ($observerId !== '') {
            $observerId .= ' - ';
        }

        $this->observerId = $observerId;
    }

    public function willCrawl(string $url, ?string $linkText, ?ResourceType $resourceType = null): void
    {
        Log::putContents("{$this->observerId}willCrawl: {$url}");
    }

    public function crawled(
        string $url,
        CrawlResponse $response,
        ?string $foundOnUrl = null,
        ?string $linkText = null,
        ?ResourceType $resourceType = null,
    ): void {
        $this->logCrawl($url, $foundOnUrl, $linkText);
    }

    public function crawlFailed(
        string $url,
        RequestException $requestException,
        ?string $foundOnUrl = null,
        ?string $linkText = null,
        ?ResourceType $resourceType = null,
    ): void {
        $this->logCrawl($url, $foundOnUrl, $linkText);
    }

    protected function logCrawl(string $url, ?string $foundOnUrl, ?string $linkText = null)
    {
        $logText = "{$this->observerId}hasBeenCrawled: {$url}";

        if ($foundOnUrl) {
            $logText .= " - found on {$foundOnUrl}";
        }

        if ($linkText) {
            $logText .= " - link text {$linkText}";
        }

        Log::putContents($logText);
    }

    public function finishedCrawling(): void
    {
        Log::putContents("{$this->observerId}finished crawling");
    }
}
