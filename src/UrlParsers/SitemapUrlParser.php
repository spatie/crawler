<?php

namespace Spatie\Crawler\UrlParsers;

use Symfony\Component\DomCrawler\Crawler as DomCrawler;

class SitemapUrlParser implements UrlParser
{
    /** @return array<string, ?string> url => linkText */
    public function extractUrls(string $html, string $baseUrl): array
    {
        $domCrawler = new DomCrawler($html, $baseUrl);

        $urls = [];

        $domCrawler->filterXPath('//loc')->each(function (DomCrawler $node) use (&$urls) {
            $url = trim($node->text());

            if (! $url) {
                return;
            }

            $parsed = parse_url($url);

            if (! isset($parsed['scheme']) || ! in_array($parsed['scheme'], ['http', 'https'])) {
                return;
            }

            $urls[$url] = null;
        });

        return $urls;
    }
}
