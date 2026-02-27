<?php

namespace Spatie\Crawler\CrawlObservers;

use GuzzleHttp\Exception\RequestException;
use Spatie\Crawler\CrawledUrl;
use Spatie\Crawler\CrawlResponse;
use Spatie\Crawler\Enums\ResourceType;

class CollectUrlsObserver extends CrawlObserver
{
    /** @var array<CrawledUrl> */
    protected array $crawledUrls = [];

    public function crawled(
        string $url,
        CrawlResponse $response,
        ?string $foundOnUrl = null,
        ?string $linkText = null,
        ?ResourceType $resourceType = null,
    ): void {
        $this->crawledUrls[] = new CrawledUrl(
            url: $url,
            status: $response->status(),
            foundOnUrl: $foundOnUrl,
            depth: $response->depth(),
            resourceType: $resourceType ?? ResourceType::Link,
        );
    }

    public function crawlFailed(
        string $url,
        RequestException $requestException,
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
