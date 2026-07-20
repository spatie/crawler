<?php

namespace Spatie\Crawler\CrawlObservers;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TooManyRedirectsException;
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
        // Guzzle 8 removed getResponse() from RequestException; it only lives on
        // response-carrying subclasses. These expose it in both Guzzle 7 and 8,
        // covering every failure that reaches here with a response.
        $response = $requestException instanceof BadResponseException || $requestException instanceof TooManyRedirectsException
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
