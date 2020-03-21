<?php

namespace Spatie\Crawler\Test;

use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlProfile;

class CrawlerProfileTest extends CrawlProfile
{
    public $xpath = '//html/body/a[4]';

    public function shouldCrawl(UriInterface $url): bool
    {
        return TRUE;
    }
}
