<?php

namespace Spatie\Crawler\CrawlObservers;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Collection;
use Spatie\Crawler\CrawledUrl;
use Spatie\Crawler\CrawlResponse;

class CollectUrlsObserver extends CrawlObserver
{
    protected Collection $crawledUrls;

    public function __construct()
    {
        $this->crawledUrls = new Collection;
    }

    public function crawled(
        string $url,
        CrawlResponse $response,
        ?string $foundOnUrl = null,
        ?string $linkText = null,
    ): void {
        $this->crawledUrls->push(new CrawledUrl(
            url: $url,
            status: $response->status(),
            foundOnUrl: $foundOnUrl,
            depth: $response->depth(),
        ));
    }

    public function crawlFailed(
        string $url,
        RequestException $requestException,
        ?string $foundOnUrl = null,
        ?string $linkText = null,
    ): void {
        $response = $requestException->getResponse();

        $this->crawledUrls->push(new CrawledUrl(
            url: $url,
            status: $response ? $response->getStatusCode() : 0,
            foundOnUrl: $foundOnUrl,
        ));
    }

    /** @return Collection<CrawledUrl> */
    public function getUrls(): Collection
    {
        return $this->crawledUrls;
    }
}
