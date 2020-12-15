<?php

declare(strict_types=1);

namespace Spatie\Crawler\Test\TestClasses;

use Spatie\Crawler\CrawlUrl;
use Spatie\Crawler\ResponseTransforms\ResponseTransformContract;

class FakeLinkInjectionTransformer implements ResponseTransformContract
{
    /**
     * Holds the fake link to inject into the response.
     *
     * @var string
     */
    private string $link;

    /**
     * Setup the fake link injector with a given link.
     *
     * @param string $link
     */
    public function __construct(string $link)
    {
        $this->link = $link;
    }

    /**
     * Append a fake link to check for successful transform execution.
     *
     * @param CrawlUrl $url
     * @param string $html
     * @return string
     */
    public function run(CrawlUrl $url, string $html): string
    {
        return $html . '<a href="' . $this->link . '">Not really in the response.</a>';
    }
}
