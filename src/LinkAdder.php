<?php

namespace Spatie\Crawler;

use GuzzleHttp\Psr7\Uri;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use Symfony\Component\DomCrawler\Link;
use Tree\Node\Node;

class LinkAdder
{
    /** @var \Spatie\Crawler\Crawler */
    protected $crawler;

    public function __construct(Crawler $crawler)
    {
        $this->crawler = $crawler;
    }

    public function addFromHtml(string $html, UriInterface $foundOnUrl)
    {
        $allLinks = $this->extractLinksFromHtml($html, $foundOnUrl);

        collect($allLinks)
            ->filter(function (UriInterface $url) {
                return $this->hasCrawlableScheme($url);
            })
            ->map(function (UriInterface $url) {
                return $this->normalizeUrl($url);
            })
            ->filter(function (UriInterface $url) use ($foundOnUrl) {
                if (! $node = $this->crawler->addToDepthTree($url, $foundOnUrl)) {
                    return false;
                }

                return $this->shouldCrawl($node);
            })
            ->filter(function (UriInterface $url) {
                return strpos($url->getPath(), '/tel:') === false;
            })
            ->each(function (UriInterface $url) use ($foundOnUrl) {
                if ($this->crawler->maximumCrawlCountReached()) {
                    return;
                }

                $crawlUrl = CrawlUrl::create($url, $foundOnUrl);

                $this->crawler->addToCrawlQueue($crawlUrl);
            });
    }

    /**
     * @param string $html
     * @param \Psr\Http\Message\UriInterface $foundOnUrl
     *
     * @return \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection|null
     */
    protected function extractLinksFromHtml(string $html, UriInterface $foundOnUrl)
    {
        $domCrawler = new DomCrawler($html, $foundOnUrl);

        return collect($domCrawler->filterXpath('//a | //link[@rel="next" or @rel="prev"]')->links())
            ->reject(function (Link $link) {
                return $link->getNode()->getAttribute('rel') === 'nofollow';
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
        if ($this->crawler->mustRespectRobots() && ! $this->crawler->getRobotsTxt()->allows($node->getValue(), $this->crawler->getUserAgent())) {
            return false;
        }

        $maximumDepth = $this->crawler->getMaximumDepth();

        if (is_null($maximumDepth)) {
            return true;
        }

        return $node->getDepth() <= $maximumDepth;
    }
}
