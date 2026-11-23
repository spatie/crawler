<?php

namespace Spatie\Crawler\CrawlObservers;

use GuzzleHttp\Exception\RequestException;
use Spatie\Crawler\CrawledUrl;
use Spatie\Crawler\CrawlProgress;
use Spatie\Crawler\CrawlResponse;
use Spatie\Crawler\Enums\ResourceType;
use Spatie\Crawler\TransferStatistics;

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
        ?TransferStatistics $transferStats = null,
    ): void {
        // Guzzle 8 removed getResponse() from the base RequestException; it now
        // lives only on its response-carrying subclasses. method_exists() keeps
        // this working across Guzzle 7 (where the base still has it) and 8, and
        // covers every response-carrying exception, not just a hardcoded subset.
        $response = method_exists($requestException, 'getResponse')
            ? $requestException->getResponse()
            : null;

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
