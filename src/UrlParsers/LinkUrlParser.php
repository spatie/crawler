<?php

namespace Spatie\Crawler\UrlParsers;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use InvalidArgumentException;
use Spatie\Crawler\Enums\ResourceType;
use Spatie\Crawler\ExtractedUrl;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use Symfony\Component\DomCrawler\Link;

class LinkUrlParser implements UrlParser
{
    /** @param array<int, ResourceType> $resourceTypes */
    public function __construct(
        protected bool $rejectNofollowLinks = true,
        protected array $resourceTypes = [ResourceType::Link],
    ) {}

    /** @return array<int, ExtractedUrl> */
    public function extractUrls(string $html, string $baseUrl): array
    {
        $domCrawler = new DomCrawler($html, $baseUrl);

        $resourceBaseUrl = $this->resolveBaseHref($domCrawler, $baseUrl);

        $urls = [];
        $seen = [];

        foreach ($this->resourceTypes as $resourceType) {
            $extracted = match ($resourceType) {
                ResourceType::Link => $this->extractLinks($domCrawler, $baseUrl),
                ResourceType::Image => $this->extractImages($domCrawler, $resourceBaseUrl),
                ResourceType::Script => $this->extractScripts($domCrawler, $resourceBaseUrl),
                ResourceType::Stylesheet => $this->extractStylesheets($domCrawler, $resourceBaseUrl),
                ResourceType::OpenGraphImage => $this->extractOpenGraphImages($domCrawler, $resourceBaseUrl),
            };

            foreach ($extracted as $extractedUrl) {
                $key = $extractedUrl->url.'|'.$extractedUrl->resourceType->value;

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $urls[] = $extractedUrl;
            }
        }

        return $urls;
    }

    /** @return array<int, ExtractedUrl> */
    protected function extractLinks(DomCrawler $domCrawler, string $baseUrl): array
    {
        $urls = [];

        $links = $domCrawler->filterXpath('//a | //link[@rel="next" or @rel="prev"] | //link[@hreflang]')->links();

        foreach ($links as $link) {
            $urls[] = $this->processLink($link, $baseUrl);
        }

        return array_filter($urls);
    }

    /** @return array<int, ExtractedUrl> */
    protected function extractImages(DomCrawler $domCrawler, string $baseUrl): array
    {
        $urls = [];

        $domCrawler->filterXpath('//img[@src or @data-src]')->each(function (DomCrawler $node) use ($baseUrl, &$urls) {
            $src = $node->attr('src') ?: $node->attr('data-src');

            if ($src) {
                $extractedUrl = $this->resolveAndCreateExtractedUrl($src, $baseUrl, ResourceType::Image);

                if ($extractedUrl !== null) {
                    $urls[] = $extractedUrl;
                }
            }
        });

        return $urls;
    }

    /** @return array<int, ExtractedUrl> */
    protected function extractScripts(DomCrawler $domCrawler, string $baseUrl): array
    {
        $urls = [];

        $domCrawler->filterXpath('//script[@src] | //link[@rel="modulepreload"]')->each(function (DomCrawler $node) use ($baseUrl, &$urls) {
            $src = $node->attr('src') ?: $node->attr('href');

            if ($src) {
                $extractedUrl = $this->resolveAndCreateExtractedUrl($src, $baseUrl, ResourceType::Script);

                if ($extractedUrl !== null) {
                    $urls[] = $extractedUrl;
                }
            }
        });

        return $urls;
    }

    /** @return array<int, ExtractedUrl> */
    protected function extractStylesheets(DomCrawler $domCrawler, string $baseUrl): array
    {
        $urls = [];

        $domCrawler->filterXpath('//link[@rel="stylesheet"] | //link[@type="text/css"] | //link[@as="style"]')->each(function (DomCrawler $node) use ($baseUrl, &$urls) {
            $href = $node->attr('href');

            if ($href) {
                $extractedUrl = $this->resolveAndCreateExtractedUrl($href, $baseUrl, ResourceType::Stylesheet);

                if ($extractedUrl !== null) {
                    $urls[] = $extractedUrl;
                }
            }
        });

        return $urls;
    }

    /** @return array<int, ExtractedUrl> */
    protected function extractOpenGraphImages(DomCrawler $domCrawler, string $baseUrl): array
    {
        $urls = [];

        $domCrawler->filterXpath('//meta[@property="og:image"] | //meta[@property="twitter:image"]')->each(function (DomCrawler $node) use ($baseUrl, &$urls) {
            $content = $node->attr('content');

            if ($content) {
                $extractedUrl = $this->resolveAndCreateExtractedUrl($content, $baseUrl, ResourceType::OpenGraphImage);

                if ($extractedUrl !== null) {
                    $urls[] = $extractedUrl;
                }
            }
        });

        return $urls;
    }

    protected function processLink(Link $link, string $baseUrl): ?ExtractedUrl
    {
        if ($this->isInvalidHrefNode($link)) {
            return null;
        }

        if ($this->rejectNofollowLinks && str_contains($link->getNode()->getAttribute('rel'), 'nofollow')) {
            return null;
        }

        try {
            $uri = $link->getUri();
        } catch (InvalidArgumentException $exception) {
            return new ExtractedUrl(
                url: $link->getNode()->getAttribute('href'),
                linkText: $link->getNode()->textContent ?: null,
                resourceType: ResourceType::Link,
                malformedReason: $exception->getMessage(),
            );
        }

        if (preg_match('/[\x00-\x1f\x7f]/', $uri)) {
            return new ExtractedUrl(
                url: $link->getNode()->getAttribute('href'),
                linkText: $link->getNode()->textContent ?: null,
                resourceType: ResourceType::Link,
                malformedReason: 'URL contains control characters',
            );
        }

        $uri = $this->removeDotSegments($uri);

        $parsed = parse_url($uri);

        if ($parsed === false) {
            return new ExtractedUrl(
                url: $link->getNode()->getAttribute('href'),
                linkText: $link->getNode()->textContent ?: null,
                resourceType: ResourceType::Link,
                malformedReason: "Unable to parse URL: {$uri}",
            );
        }

        if (! isset($parsed['scheme']) || ! in_array($parsed['scheme'], ['http', 'https'])) {
            return null;
        }

        if (str_contains($parsed['path'] ?? '', '/tel:')) {
            return null;
        }

        // Strip fragment
        $uri = explode('#', $uri, 2)[0];

        $linkText = $link->getNode()->textContent;
        if ($linkText) {
            // Limit link text to prevent excessive memory usage from large DOM nodes
            $linkText = substr($linkText, 0, 4000);
        }

        return new ExtractedUrl(
            url: $uri,
            linkText: $linkText ?: null,
            resourceType: ResourceType::Link,
        );
    }

    protected function resolveAndCreateExtractedUrl(string $src, string $baseUrl, ResourceType $resourceType): ?ExtractedUrl
    {
        try {
            $resolved = (string) UriResolver::resolve(new Uri($baseUrl), new Uri($src));
        } catch (InvalidArgumentException $exception) {
            return new ExtractedUrl(
                url: $src,
                resourceType: $resourceType,
                malformedReason: $exception->getMessage(),
            );
        }

        if (preg_match('/[\x00-\x1f\x7f]/', $resolved)) {
            return new ExtractedUrl(
                url: $src,
                resourceType: $resourceType,
                malformedReason: 'URL contains control characters',
            );
        }

        $parsed = parse_url($resolved);

        if (! isset($parsed['scheme']) || ! in_array($parsed['scheme'], ['http', 'https'])) {
            return null;
        }

        // Strip fragment
        $resolved = explode('#', $resolved, 2)[0];

        return new ExtractedUrl(
            url: $resolved,
            resourceType: $resourceType,
        );
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

    protected function removeDotSegments(string $uri): string
    {
        $parsed = parse_url($uri);

        if (! isset($parsed['path']) || ! str_contains($parsed['path'], '.')) {
            return $uri;
        }

        $path = $parsed['path'];
        $output = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($output);

                continue;
            }

            $output[] = $segment;
        }

        $normalizedPath = implode('/', $output);

        if ($normalizedPath !== '' && $normalizedPath[0] !== '/' && ($path[0] ?? '') === '/') {
            $normalizedPath = '/'.$normalizedPath;
        }

        $result = ($parsed['scheme'] ?? '').'://';

        if (isset($parsed['user'])) {
            $result .= $parsed['user'];
            if (isset($parsed['pass'])) {
                $result .= ':'.$parsed['pass'];
            }
            $result .= '@';
        }

        $result .= $parsed['host'] ?? '';

        if (isset($parsed['port'])) {
            $result .= ':'.$parsed['port'];
        }

        $result .= $normalizedPath;

        if (isset($parsed['query'])) {
            $result .= '?'.$parsed['query'];
        }

        if (isset($parsed['fragment'])) {
            $result .= '#'.$parsed['fragment'];
        }

        return $result;
    }

    protected function resolveBaseHref(DomCrawler $domCrawler, string $baseUrl): string
    {
        $baseHrefNodes = $domCrawler->filterXpath('//base[@href]');

        if ($baseHrefNodes->count() === 0) {
            return $baseUrl;
        }

        $baseHref = $baseHrefNodes->first()->attr('href');

        if (! $baseHref) {
            return $baseUrl;
        }

        try {
            return (string) UriResolver::resolve(new Uri($baseUrl), new Uri($baseHref));
        } catch (InvalidArgumentException) {
            return $baseUrl;
        }
    }
}
