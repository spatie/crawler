<?php

namespace Spatie\Crawler\UrlParsers;

use InvalidArgumentException;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use Symfony\Component\DomCrawler\Link;

class LinkUrlParser implements UrlParser
{
    public function __construct(
        protected bool $rejectNofollowLinks = true,
    ) {}

    /** @return array<string, ?string> url => linkText */
    public function extractUrls(string $html, string $baseUrl): array
    {
        $domCrawler = new DomCrawler($html, $baseUrl);

        $urls = [];

        $links = $domCrawler->filterXpath('//a | //link[@rel="next" or @rel="prev"]')->links();

        foreach ($links as $link) {
            if ($this->isInvalidHrefNode($link)) {
                continue;
            }

            if ($this->rejectNofollowLinks && str_contains($link->getNode()->getAttribute('rel'), 'nofollow')) {
                continue;
            }

            try {
                $uri = $link->getUri();
            } catch (InvalidArgumentException) {
                continue;
            }

            $parsed = parse_url($uri);

            if (! isset($parsed['scheme']) || ! in_array($parsed['scheme'], ['http', 'https'])) {
                continue;
            }

            if (str_contains($parsed['path'] ?? '', '/tel:')) {
                continue;
            }

            // Strip fragment
            $uri = strtok($uri, '#');

            $linkText = $link->getNode()->textContent;
            if ($linkText) {
                $linkText = substr($linkText, 0, 4000);
            }

            $urls[$uri] = $linkText ?: null;
        }

        return $urls;
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
