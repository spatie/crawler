<?php

namespace Spatie\Crawler\CrawlObservers;

use Closure;
use GuzzleHttp\Exception\RequestException;
use Spatie\Crawler\CrawlProgress;
use Spatie\Crawler\CrawlResponse;
use Spatie\Crawler\CrawlUrl;
use Spatie\Crawler\Enums\FinishReason;
use Spatie\Crawler\Enums\ResourceType;

class CrawlObserverCollection
{
    protected array $observers = [];

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
        $this->observers[] = new class($callback) extends CrawlObserver {
            public function __construct(protected Closure $callback) {}

            public function willCrawl(string $url, ?string $linkText, ?ResourceType $resourceType = null): void
            {
                ($this->callback)($url, $linkText, $resourceType);
            }
        };
    }

    public function onCrawled(Closure $callback): void
    {
        $this->observers[] = new class($callback) extends CrawlObserver {
            public function __construct(protected Closure $callback) {}

            public function crawled(string $url, CrawlResponse $response, CrawlProgress $progress): void
            {
                ($this->callback)($url, $response, $progress);
            }
        };
    }

    public function onFailed(Closure $callback): void
    {
        $this->observers[] = new class($callback) extends CrawlObserver {
            public function __construct(protected Closure $callback) {}

            public function crawlFailed(
                string $url,
                RequestException $requestException,
                CrawlProgress $progress,
                ?string $foundOnUrl = null,
                ?string $linkText = null,
                ?ResourceType $resourceType = null,
            ): void {
                ($this->callback)($url, $requestException, $progress, $foundOnUrl, $linkText, $resourceType);
            }
        };
    }

    public function onFinished(Closure $callback): void
    {
        $this->observers[] = new class($callback) extends CrawlObserver {
            public function __construct(protected Closure $callback) {}

            public function finishedCrawling(FinishReason $reason, CrawlProgress $progress): void
            {
                ($this->callback)($reason, $progress);
            }
        };
    }

    public function willCrawl(CrawlUrl $crawlUrl): void
    {
        foreach ($this->observers as $observer) {
            $observer->willCrawl($crawlUrl->url, $crawlUrl->linkText, $crawlUrl->resourceType);
        }
    }

    public function crawled(CrawlUrl $crawlUrl, CrawlResponse $response, CrawlProgress $progress): void
    {
        foreach ($this->observers as $observer) {
            $observer->crawled($crawlUrl->url, $response, $progress);
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
    }

    public function finishedCrawling(FinishReason $reason, CrawlProgress $progress): void
    {
        foreach ($this->observers as $observer) {
            $observer->finishedCrawling($reason, $progress);
        }
    }
}
