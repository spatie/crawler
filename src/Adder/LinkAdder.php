<?php

namespace Spatie\Crawler\Adder;

use GuzzleHttp\Psr7\Uri;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use Symfony\Component\DomCrawler\Link;
use Tree\Node\Node;

class LinkAdder extends AbstractAdder
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

        return collect($domCrawler->filterXpath('//a | //link[@rel="next" or @rel="prev"]')->links())
            ->reject(function (Link $link) {
                if ($this->isInvalidHrefNode($link)) {
                    return true;
                }

                if ($link->getNode()->getAttribute('rel') === 'nofollow') {
                    return true;
                }

                return false;
            })
            ->map(function (Link $link) {
                try {
                    return new Uri($link->getUri());
                } catch (InvalidArgumentException $exception) {
                    return;
                }
            })
            ->filter();
    }

    protected function isInvalidHrefNode(Link $link): bool
    {
        if ($link->getNode()->nodeName !== 'a') {
            return false;
        }

        if ($link->getNode()->nextSibling !== null) {
            return false;
        }

        if ($link->getNode()->childNodes->length !== 0) {
            return false;
        }

        return true;
    }
}
