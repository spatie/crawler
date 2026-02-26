<?php

namespace Spatie\Crawler\CrawlObservers;

use Closure;
use GuzzleHttp\Exception\RequestException;
use Spatie\Crawler\CrawlResponse;
use Spatie\Crawler\CrawlUrl;

class CrawlObserverCollection
{
    protected array $observers = [];

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
            $observer->willCrawl($crawlUrl->url, $crawlUrl->linkText);
        }
    }

    public function crawled(CrawlUrl $crawlUrl, CrawlResponse $response): void
    {
        foreach ($this->observers as $observer) {
            $observer->crawled(
                $crawlUrl->url,
                $response,
                $crawlUrl->foundOnUrl,
                $crawlUrl->linkText,
            );
        }

        foreach ($this->onCrawledCallbacks as $callback) {
            $callback($crawlUrl->url, $response);
        }
    }

    public function crawlFailed(CrawlUrl $crawlUrl, RequestException $exception): void
    {
        foreach ($this->observers as $observer) {
            $observer->crawlFailed(
                $crawlUrl->url,
                $exception,
                $crawlUrl->foundOnUrl,
                $crawlUrl->linkText,
            );
        }

        foreach ($this->onFailedCallbacks as $callback) {
            $callback($crawlUrl->url, $exception);
        }
    }

    public function finishedCrawling(): void
    {
        foreach ($this->observers as $observer) {
            $observer->finishedCrawling();
        }

        foreach ($this->onFinishedCallbacks as $callback) {
            $callback();
        }
    }
}
