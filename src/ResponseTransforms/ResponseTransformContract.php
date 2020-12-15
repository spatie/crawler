<?php

declare(strict_types=1);

namespace Spatie\Crawler\ResponseTransforms;

use Spatie\Crawler\CrawlUrl;

interface ResponseTransformContract
{
    /**
     * Execute given transformation on the response HTML.
     *
     * @param CrawlUrl $url
     * @param string $html
     * @return string
     */
    public function run(CrawlUrl $url, string $html): string;
}
