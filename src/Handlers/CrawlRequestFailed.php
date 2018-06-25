<?php

namespace Spatie\Crawler\Handlers;

use Spatie\Crawler\Crawler;
use GuzzleHttp\Exception\RequestException;

abstract class CrawlRequestFailed
{
    /** @var \Spatie\Crawler\Crawler */
    protected $crawler;

    public function __construct(Crawler $crawler)
    {
        $this->crawler = $crawler;
    }

    abstract public function __invoke(RequestException $exception, $index);
}
