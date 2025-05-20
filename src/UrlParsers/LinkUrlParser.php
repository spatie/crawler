<?php

namespace Spatie\Crawler\UrlParsers;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlUrl;
use Spatie\Crawler\Url;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use Symfony\Component\DomCrawler\Link;
use Tree\Node\Node;

class LinkUrlParser implements UrlParser
{
    protected Crawler $crawler;

    public function __construct(Crawler $crawler)
    {
        $this->crawler = $crawler;
    }

    public function addFromHtml(string $html, UriInterface $foundOnUrl, ?UriInterface $originalUrl = null): void
    {
        $allLinks = $this->extractLinksFromHtml($html, $foundOnUrl);

        collect($allLinks)
            ->filter(fn (Url $url) => $this->hasCrawlableScheme($url))
            ->map(fn (Url $url) => $this->normalizeUrl($url))
            ->filter(function (Url $url) use ($foundOnUrl, $originalUrl) {
                if (! $node = $this->crawler->addToDepthTree($url, $foundOnUrl, null, $originalUrl)) {
                    return false;
                }

                return $this->shouldCrawl($node);
            })
            ->filter(fn (Url $url) => ! str_contains($url->getPath(), '/tel:'))
            ->each(function (Url $url) use ($foundOnUrl) {
                $crawlUrl = CrawlUrl::create($url, $foundOnUrl, linkText: $url->linkText());

                $this->crawler->addToCrawlQueue($crawlUrl);
            });
    }

    protected function extractLinksFromHtml(string $html, UriInterface $foundOnUrl): ?Collection
    {
        $domCrawler = new DomCrawler($html, $foundOnUrl);

        return collect($domCrawler->filterXpath('//a | //link[@rel="next" or @rel="prev"]')->links())
            ->reject(function (Link $link) {
                if ($this->isInvalidHrefNode($link)) {
                    return true;
                }

                if ($this->crawler->mustRejectNofollowLinks() && str_contains($link->getNode()->getAttribute('rel'), 'nofollow')) {
                    return true;
                }

                return false;
            })
            ->map(function (Link $link) {
                try {
                    $linkText = $link->getNode()->textContent;

                    if ($linkText) {
                        $linkText = substr($linkText, 0, 4000);
                    }

                    return new Url($link->getUri(), $linkText);
                } catch (InvalidArgumentException $exception) {
                    return null;
                }
            })
            ->filter();
    }

    protected function hasCrawlableScheme(UriInterface $uri): bool
    {
        return in_array($uri->getScheme(), ['http', 'https']);
    }

    protected function normalizeUrl(UriInterface $url): UriInterface
    {
        return $url->withFragment('');
    }

    protected function shouldCrawl(Node $node): bool
    {
        $mustRespectRobots = $this->crawler->mustRespectRobots();
        $robotsTxt = $this->crawler->getRobotsTxt();

        if ($mustRespectRobots && $robotsTxt !== null) {
            $isAllowed = $robotsTxt->allows($node->getValue(), $this->crawler->getUserAgent());
            if (! $isAllowed) {
                return false;
            }
        }

        $maximumDepth = $this->crawler->getMaximumDepth();

        if (is_null($maximumDepth)) {
            return true;
        }

        return $node->getDepth() <= $maximumDepth;
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
