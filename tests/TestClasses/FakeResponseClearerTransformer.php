<?php

declare(strict_types=1);

namespace Spatie\Crawler\Test\TestClasses;

use Spatie\Crawler\CrawlUrl;
use Spatie\Crawler\ResponseTransforms\ResponseTransformContract;

class FakeResponseClearerTransformer implements ResponseTransformContract
{
    /**
     * Clear the response entirely.
     *
     * @param CrawlUrl $url
     * @param string $html
     * @return string
     */
    public function run(CrawlUrl $url, string $html): string
    {
        return '';
    }
}
