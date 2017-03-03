<?php

namespace Spatie\Crawler;

use Psr\Http\Message\UriInterface;

class CrawlUrl
{
    /** @var UriInterface */
    public $url;

    /** @var UriInterface */
    public $foundOnUrl;

    public static function create(UriInterface $url, UriInterface $foundOnUrl = null)
    {
        return new static($url, $foundOnUrl);
    }

    protected function __construct(UriInterface $url, UriInterface $foundOnUrl = null)
    {
        $this->url = $url;

        $this->foundOnUrl = $foundOnUrl;
    }
}
