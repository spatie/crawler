<?php

namespace Spatie\Crawler\RetryProfile;

use Spatie\Crawler\CrawlUrl;
use Spatie\Crawler\RetryProfile;
use GuzzleHttp\Exception\RequestException;

class NoRetry implements RetryProfile
{
    public function shouldRetry(CrawlUrl $url, RequestException $exception) : bool
    {
        return false;
    }
}
