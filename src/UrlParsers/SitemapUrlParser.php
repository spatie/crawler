<?php

namespace Spatie\Crawler\UrlParsers;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlUrl;
use Spatie\Crawler\Url;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use Tree\Node\Node;

class SitemapUrlParser implements UrlParser
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

        return collect($domCrawler->filterXPath('//loc')
            ->each(function (DomCrawler $node) {
                try {
                    $linkText = $node->text();

                    if ($linkText) {
                        $linkText = substr($linkText, 0, 4000);
                    }

                    return new Url($linkText, $linkText);
                } catch (InvalidArgumentException $exception) {
                    return null;
                }
            }));
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
}
