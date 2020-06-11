<?php

namespace Spatie\Crawler\Adder;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use Symfony\Component\DomCrawler\Image;

class ImageUrlAdder extends AbstractAdder
{
    /**
     * @param string $html
     * @param \Psr\Http\Message\UriInterface $foundOnUrl
     *
     * @return \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection|null
     */
    protected function extractUrlsFromHtml(string $html, UriInterface $foundOnUrl)
    {
        $domCrawler = new DomCrawler($html, $foundOnUrl);

        return collect($domCrawler->filterXpath('//img')->images())
            ->map(function (Image $image) {
                try {
                    return new Uri($image->getUri());
                } catch (InvalidArgumentException $exception) {
                    return;
                }
            })
            ->filter();
    }
}
