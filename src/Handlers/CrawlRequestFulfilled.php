<?php

namespace Spatie\Crawler\Handlers;

use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlerRobots;
use Spatie\Crawler\CrawlUrl;
use Spatie\Crawler\LinkAdder;
use Spatie\Crawler\CrawlSubdomains;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;

class CrawlRequestFulfilled
{
    /** @var \Spatie\Crawler\Crawler */
    protected $crawler;

    /** @var \Spatie\Crawler\LinkAdder */
    protected $linkAdder;

    public function __construct(Crawler $crawler)
    {
        $this->crawler = $crawler;

        $this->linkAdder = new LinkAdder($this->crawler);
    }

    public function __invoke(ResponseInterface $response, $index)
    {
        $robots = new CrawlerRobots($response, $this->crawler->mustRespectRobots());

        if (! $robots->mayIndex()) {
            return;
        }

        $crawlUrl = $this->crawler->getCrawlQueue()->getUrlById($index);

        $this->handleCrawled($response, $crawlUrl);

        if (! $this->crawler->getCrawlProfile() instanceof CrawlSubdomains) {
            if ($crawlUrl->url->getHost() !== $this->crawler->getBaseUrl()->getHost()) {
                return;
            }
        }

        if (! $robots->mayFollow()) {
            return;
        }

        $body = $this->convertBodyToString($response->getBody(), $this->crawler->getMaximumResponseSize());

        $this->linkAdder->addFromHtml($body, $crawlUrl->url);
    }

    protected function handleCrawled(ResponseInterface $response, CrawlUrl $crawlUrl)
    {
        $this->crawler->getCrawlObservers()->crawled($crawlUrl, $response);
    }

    protected function convertBodyToString(StreamInterface $bodyStream, $readMaximumBytes = 1024 * 1024 * 2): string
    {
        $bodyStream->rewind();

        $body = $bodyStream->read($readMaximumBytes);

        return $body;
    }
}
