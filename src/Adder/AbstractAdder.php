<?php

namespace Spatie\Crawler\Adder;

use Psr\Http\Message\UriInterface;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlUrl;
use Tree\Node\Node;

abstract class AbstractAdder
{
    /** @var \Spatie\Crawler\Crawler */
    protected $crawler;

    public function __construct(Crawler $crawler)
    {
        $this->crawler = $crawler;
    }

    public function addFromHtml(string $html, UriInterface $foundOnUrl)
    {
        $allUrls = $this->extractUrlsFromHtml($html, $foundOnUrl);

        collect($allUrls)
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

    abstract protected function extractUrlsFromHtml(string $html, UriInterface $foundOnUrl);

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
