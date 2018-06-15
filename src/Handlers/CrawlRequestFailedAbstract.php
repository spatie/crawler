<?php

namespace Spatie\Crawler\Handlers;

use GuzzleHttp\Exception\RequestException;
use Spatie\Crawler\Crawler;

abstract class CrawlRequestFailedAbstract
{
    /** @var \Spatie\Crawler\Crawler */
    protected $crawler;

    public function __construct(Crawler $crawler)
    {
        $this->crawler = $crawler;
    }

    abstract public function __invoke(RequestException $exception, $index);
}
