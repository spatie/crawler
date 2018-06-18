<?php

namespace Spatie\Crawler\Handlers;

use Psr\Http\Message\ResponseInterface;
use Spatie\Crawler\CrawlerRobots;
use Spatie\Crawler\CrawlSubdomains;

class DefaultCrawlRequestFulfilled extends CrawlRequestFulfilled
{
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
}
