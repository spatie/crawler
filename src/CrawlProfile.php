<?php

namespace Spatie\Crawler;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

abstract class CrawlProfile
{
    /**
     * @var \Psr\Http\Message\UriInterface
     */
    protected $baseUrl;

    /**
     * @param \Psr\Http\Message\UriInterface|string|null $baseUrl
     */
    public function __construct($baseUrl = '')
    {
        if (! $baseUrl instanceof UriInterface) {
            $baseUrl = new Uri($baseUrl);
        }

        $this->baseUrl = $baseUrl;
    }

    /**
     * Return url associated with the profile.
     *
     * @return \Psr\Http\Message\UriInterface
     */
    public function getBaseUrl(): UriInterface
    {
        return $this->baseUrl;
    }

    /**
     * Determine if the given url should be crawled.
     *
     * @param \Psr\Http\Message\UriInterface $url
     *
     * @return bool
     */
    abstract public function shouldCrawl(UriInterface $url): bool;
}
