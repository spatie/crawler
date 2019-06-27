<?php

namespace Spatie\Crawler;

use Psr\Http\Message\UriInterface;

class CrawlUrl
{
    /** @var \Psr\Http\Message\UriInterface */
    public $url;

    /** @var \Psr\Http\Message\UriInterface */
    public $foundOnUrl;

    public static function create(UriInterface $url, ?UriInterface $foundOnUrl = null)
    {
        $static = new static($url, $foundOnUrl);

        return $static;
    }

    protected function __construct(UriInterface $url, $foundOnUrl = null)
    {
        $this->url = $url;
        $this->foundOnUrl = $foundOnUrl;
    }
}
