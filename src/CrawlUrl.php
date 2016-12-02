<?php

namespace Spatie\Crawler;

class CrawlUrl
{
    /** @var \Spatie\Crawler\Url */
    public $url;

    /** @var \Spatie\Crawler\Url */
    public $foundOnUrl;

    public static function create(Url $url, Url $foundOnUrl = null)
    {
        return new static($url, $foundOnUrl);
    }

    protected function __construct(Url $url, Url $foundOnUrl = null)
    {
        $this->url = $url;

        $this->foundOnUrl = $foundOnUrl;
    }
}
