<?php

namespace Spatie\Crawler\CrawlObservers;

use Closure;
use GuzzleHttp\Exception\RequestException;
use Spatie\Crawler\CrawlProgress;
use Spatie\Crawler\CrawlResponse;
use Spatie\Crawler\CrawlUrl;
use Spatie\Crawler\Enums\FinishReason;

class CrawlObserverCollection
{
    protected array $observers = [];

    protected array $onWillCrawlCallbacks = [];

    protected array $onCrawledCallbacks = [];

    protected array $onFailedCallbacks = [];

    protected array $onFinishedCallbacks = [];

    public function __construct(array $observers = [])
    {
        $this->observers = $observers;
    }

    public function addObserver(CrawlObserver $observer): void
    {
        $this->observers[] = $observer;
    }

    public function onWillCrawl(Closure $callback): void
    {
        $this->onWillCrawlCallbacks[] = $callback;
    }

    public function onCrawled(Closure $callback): void
    {
        $this->onCrawledCallbacks[] = $callback;
    }

    public function onFailed(Closure $callback): void
    {
        $this->onFailedCallbacks[] = $callback;
    }

    public function onFinished(Closure $callback): void
    {
        $this->onFinishedCallbacks[] = $callback;
    }

    public function willCrawl(CrawlUrl $crawlUrl): void
    {
        foreach ($this->observers as $observer) {
            $observer->willCrawl($crawlUrl->url, $crawlUrl->linkText, $crawlUrl->resourceType);
        }

        foreach ($this->onWillCrawlCallbacks as $callback) {
            $callback($crawlUrl->url, $crawlUrl->linkText, $crawlUrl->resourceType);
        }
    }

    public function crawled(CrawlUrl $crawlUrl, CrawlResponse $response, CrawlProgress $progress): void
    {
        foreach ($this->observers as $observer) {
            $observer->crawled(
                $crawlUrl->url,
                $response,
                $progress,
            );
        }

        foreach ($this->onCrawledCallbacks as $callback) {
            $callback($crawlUrl->url, $response, $progress);
        }
    }

    public function crawlFailed(CrawlUrl $crawlUrl, RequestException $exception, CrawlProgress $progress): void
    {
        foreach ($this->observers as $observer) {
            $observer->crawlFailed(
                $crawlUrl->url,
                $exception,
                $progress,
                $crawlUrl->foundOnUrl,
                $crawlUrl->linkText,
                $crawlUrl->resourceType,
            );
        }

        foreach ($this->onFailedCallbacks as $callback) {
            $callback($crawlUrl->url, $exception, $progress, $crawlUrl->foundOnUrl, $crawlUrl->linkText, $crawlUrl->resourceType);
        }
    }

    public function finishedCrawling(FinishReason $reason, CrawlProgress $progress): void
    {
        foreach ($this->observers as $observer) {
            $observer->finishedCrawling($reason, $progress);
        }

        foreach ($this->onFinishedCallbacks as $callback) {
            $callback($reason, $progress);
        }
    }
}
