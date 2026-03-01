<?php

namespace Spatie\Crawler\CrawlObservers;

use GuzzleHttp\Exception\RequestException;
use Spatie\Crawler\CrawledUrl;
use Spatie\Crawler\CrawlProgress;
use Spatie\Crawler\CrawlResponse;
use Spatie\Crawler\Enums\ResourceType;

class CollectUrlsObserver extends CrawlObserver
{
    /** @var array<CrawledUrl> */
    protected array $crawledUrls = [];

    public function crawled(
        string $url,
        CrawlResponse $response,
        CrawlProgress $progress,
    ): void {
        $this->crawledUrls[] = new CrawledUrl(
            url: $url,
            status: $response->status(),
            foundOnUrl: $response->foundOnUrl(),
            depth: $response->depth(),
            resourceType: $response->resourceType(),
        );
    }

    public function crawlFailed(
        string $url,
        RequestException $requestException,
        CrawlProgress $progress,
        ?string $foundOnUrl = null,
        ?string $linkText = null,
        ?ResourceType $resourceType = null,
    ): void {
        $response = $requestException->getResponse();

        $this->crawledUrls[] = new CrawledUrl(
            url: $url,
            status: $response ? $response->getStatusCode() : 0,
            foundOnUrl: $foundOnUrl,
            resourceType: $resourceType ?? ResourceType::Link,
        );
    }

    /** @return array<CrawledUrl> */
    public function getUrls(): array
    {
        return $this->crawledUrls;
    }
}
