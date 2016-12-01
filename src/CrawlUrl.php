<?php

namespace Spatie\Crawler;

class CrawlUrl
{
    const STATUS_NOT_YET_CRAWLED = 'not_yet_crawled';
    const STATUS_BUSY_CRAWLING = 'busy_crawling';
    const STATUS_HAS_BEEN_CRAWLED = 'has_been_crawled';

    /** @var \Spatie\Crawler\Url */
    public $url;

    /** @var string */
    public $status;

    /** @var \Spatie\Crawler\Url */
    public $foundOnUrl;

    public static function create(Url $url, Url $foundOnUrl = null)
    {
        return new static($url, static::STATUS_NOT_YET_CRAWLED, $foundOnUrl);
    }

    protected function __construct(Url $url, string $status, Url $foundOnUrl = null)
    {
        $this->url = $url;

        $this->status = $status;

        $this->foundOnUrl = $foundOnUrl;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;

        return $this;
    }
}
