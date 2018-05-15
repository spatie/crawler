<?php

namespace Spatie\Crawler\Handlers;

use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlUrl;
use Spatie\Crawler\LinkAdder;
use Spatie\Robots\RobotsMeta;
use Spatie\Robots\RobotsHeaders;
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
        $crawlUrl = $this->crawler->getCrawlQueue()->getUrlById($index);

        $body = $this->convertBodyToString($response->getBody(), $this->crawler->getMaximumResponseSize());

        $robotsHeaders = RobotsHeaders::create($response->getHeaders());

        $robotsMeta = RobotsMeta::create($body);

        if (! $this->mayIndex($robotsHeaders, $robotsMeta)) {
            return;
        }

        $this->handleCrawled($response, $crawlUrl);

        if (! $this->crawler->getCrawlProfile() instanceof CrawlSubdomains) {
            if ($crawlUrl->url->getHost() !== $this->crawler->getBaseUrl()->getHost()) {
                return;
            }
        }

        if (! $this->mayFollow($robotsHeaders, $robotsMeta)) {
            return;
        }

        $this->linkAdder->addFromHtml($body, $crawlUrl->url);
    }

    protected function convertBodyToString(StreamInterface $bodyStream, $readMaximumBytes = 1024 * 1024 * 2): string
    {
        $bodyStream->rewind();

        $body = $bodyStream->read($readMaximumBytes);

        return $body;
    }

    protected function handleCrawled(ResponseInterface $response, CrawlUrl $crawlUrl)
    {
        foreach ($this->crawler->getCrawlObservers() as $crawlObserver) {
            $crawlObserver->crawled(
                $crawlUrl->url,
                $response,
                $crawlUrl->foundOnUrl
            );
        }
    }

    protected function mayIndex(RobotsHeaders $robotsHeaders, RobotsMeta $robotsMeta): bool
    {
        if (! $this->crawler->mustRespectRobots()) {
            return true;
        }

        if (! $robotsHeaders->mayIndex()) {
            return false;
        }

        if (! $robotsMeta->mayIndex()) {
            return false;
        }

        return true;
    }

    protected function mayFollow(RobotsHeaders $robotsHeaders, RobotsMeta $robotsMeta): bool
    {
        if (! $this->crawler->mustRespectRobots()) {
            return true;
        }

        if (! $robotsHeaders->mayFollow()) {
            return false;
        }

        if (! $robotsMeta->mayFollow()) {
            return false;
        }

        return true;
    }
}
