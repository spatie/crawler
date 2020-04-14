<?php

namespace Spatie\Crawler\Handlers;

use function GuzzleHttp\Psr7\stream_for;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RedirectMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlerRobots;
use Spatie\Crawler\CrawlSubdomains;
use Spatie\Crawler\CrawlUrl;
use Spatie\Crawler\LinkAdder;

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
        $body = $this->getBody($response);

        $robots = new CrawlerRobots(
            $response->getHeaders(),
            $body,
            $this->crawler->mustRespectRobots()
        );

        $crawlUrl = $this->crawler->getCrawlQueue()->getUrlById($index);

        if ($this->crawler->mayExecuteJavaScript()) {
            $body = $this->getBodyAfterExecutingJavaScript($crawlUrl->url);

            $response = $response->withBody(stream_for($body));
        }

        if ($robots->mayIndex()) {
            $this->handleCrawled($response, $crawlUrl);
        }

        if (! $this->crawler->getCrawlProfile() instanceof CrawlSubdomains) {
            if ($crawlUrl->url->getHost() !== $this->crawler->getBaseUrl()->getHost()) {
                return;
            }
        }

        if (! $robots->mayFollow()) {
            return;
        }

        $baseUrl = $this->getBaseUrl($response, $crawlUrl);

        $this->linkAdder->addFromHtml($body, $baseUrl);

        usleep($this->crawler->getDelayBetweenRequests());
    }

    protected function getBaseUrl(ResponseInterface $response, CrawlUrl $crawlUrl)
    {
        $redirectHistory = $response->getHeader(RedirectMiddleware::HISTORY_HEADER);

        if (empty($redirectHistory)) {
            return $crawlUrl->url;
        }

        return new Uri(end($redirectHistory));
    }

    protected function handleCrawled(ResponseInterface $response, CrawlUrl $crawlUrl)
    {
        $this->crawler->getCrawlObservers()->crawled($crawlUrl, $response);
    }

    protected function convertBodyToString(StreamInterface $bodyStream, $readMaximumBytes = 1024 * 1024 * 2): string
    {
        if ($bodyStream->isSeekable()) {
            $bodyStream->rewind();
        }

        $body = $bodyStream->read($readMaximumBytes);

        return $body;
    }

    protected function getBody(ResponseInterface $response): string
    {
        $contentType = $response->getHeaderLine('Content-Type');

        if (! $this->isMimetypeAllowedToParse($contentType)) {
            return '';
        }

        return $this->convertBodyToString($response->getBody(), $this->crawler->getMaximumResponseSize());
    }

    protected function getBodyAfterExecutingJavaScript(UriInterface $url): string
    {
        $browsershot = $this->crawler->getBrowsershot();

        $html = $browsershot->setUrl((string) $url)->bodyHtml();

        return html_entity_decode($html);
    }

    protected function isMimetypeAllowedToParse($contentType): bool
    {
        if (empty($contentType)) {
            return true;
        }

        if (! count($this->crawler->getParseableMimeTypes())) {
            return true;
        }

        foreach ($this->crawler->getParseableMimeTypes() as $allowedType) {
            if (stristr($contentType, $allowedType)) {
                return true;
            }
        }

        return false;
    }
}
