<?php

namespace Spatie\Crawler;

class CrawlUrl
{
    const STATUS_NOT_YET_CRAWLED = 'not_yet_crawled';
    const STATUS_BUSY_CRAWLING = 'busy_crawling';
    const STATUS_HAS_BEEN_CRAWLED = 'has_been_crawled';

    /** @var  \Spatie\Crawler\Url */
    public $url;

    /** @var  string */
    public $status;

    public static function create(Url $url)
    {
        return new static($url, static::STATUS_NOT_YET_CRAWLED);
    }

    protected function __construct(Url $url, string $status)
    {
        $this->url = $url;

        $this->status = $status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;

        return $this;
    }


}