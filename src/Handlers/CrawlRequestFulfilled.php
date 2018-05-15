<?php

namespace Spatie\Crawler\Handlers;

use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlUrl;
use Spatie\Robots\RobotsMeta;
use Spatie\Crawler\CrawlProfile;
use Spatie\Robots\RobotsHeaders;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlSubdomains;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;
use Spatie\Crawler\CrawlQueue\CrawlQueue;

class CrawlRequestFulfilled
{
    /** @var \Spatie\Crawler\Crawler */
    protected $crawler;

    /** @var \Spatie\Crawler\CrawlQueue\CrawlQueue */
    protected $crawlQueue;

    /** @var \Spatie\Crawler\CrawlProfile */
    protected $crawlProfile;

    /** @var array[\Spatie\Crawler\CrawlObserver] */
    protected $crawlObservers;

    /** @var \Psr\Http\Message\UriInterface */
    protected $baseUrl;

    /** @var int */
    protected $maximumResponseSize;

    /** @var bool */
    protected $respectRobots;

    public function __construct(
        Crawler $crawler,
        UriInterface $baseUrl,
        CrawlQueue $crawlQueue,
        CrawlProfile $crawlProfile,
        array $crawlObservers,
        int $maximumResponseSize,
        bool $respectRobots
    ) {
        $this->crawler = $crawler;
        $this->baseUrl = $baseUrl;
        $this->crawlQueue = $crawlQueue;
        $this->crawlProfile = $crawlProfile;
        $this->crawlObservers = $crawlObservers;
        $this->maximumResponseSize = $maximumResponseSize;
        $this->respectRobots = $respectRobots;
    }

    public function __invoke(ResponseInterface $response, $index)
    {
        $crawlUrl = $this->crawlQueue->getUrlById($index);

        $body = $this->convertBodyToString($response->getBody(), $this->maximumResponseSize);

        $robotsHeaders = RobotsHeaders::create($response->getHeaders());

        $robotsMeta = RobotsMeta::create($body);

        if (! $this->mayIndex($robotsHeaders, $robotsMeta)) {
            return;
        }

        $this->handleCrawled($response, $crawlUrl);

        if (! $this->crawlProfile instanceof CrawlSubdomains) {
            if ($crawlUrl->url->getHost() !== $this->baseUrl->getHost()) {
                return;
            }
        }

        if (! $this->mayFollow($robotsHeaders, $robotsMeta)) {
            return;
        }

        $this->crawler->addAllLinksToCrawlQueue(
            $body,
            $crawlUrl->url
        );
    }

    protected function convertBodyToString(StreamInterface $bodyStream, $readMaximumBytes = 1024 * 1024 * 2): string
    {
        $bodyStream->rewind();

        $body = $bodyStream->read($readMaximumBytes);

        return $body;
    }

    protected function handleCrawled(ResponseInterface $response, CrawlUrl $crawlUrl)
    {
        foreach ($this->crawlObservers as $crawlObserver) {
            $crawlObserver->crawled(
                $crawlUrl->url,
                $response,
                $crawlUrl->foundOnUrl
            );
        }
    }

    protected function mayIndex(RobotsHeaders $robotsHeaders, RobotsMeta $robotsMeta): bool
    {
        if (! $this->respectRobots) {
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
        if (! $this->respectRobots) {
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
