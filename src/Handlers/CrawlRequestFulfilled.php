<?php

namespace Spatie\Crawler\Handlers;

use Exception;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\RedirectMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlerRobots;
use Spatie\Crawler\CrawlProfiles\CrawlSubdomains;
use Spatie\Crawler\CrawlUrl;
use Spatie\Crawler\ResponseWithCachedBody;
use Spatie\Crawler\UrlParsers\UrlParser;
use Symfony\Component\Process\Exception\ProcessFailedException;

class CrawlRequestFulfilled
{
    protected UrlParser $urlParser;

    public function __construct(protected Crawler $crawler)
    {
        $urlParserClass = $this->crawler->getUrlParserClass();
        $this->urlParser = new $urlParserClass($this->crawler);
    }

    public function __invoke(ResponseInterface $response, $index)
    {
        $body = $this->getBody($response);
        if (empty($body)) {
            usleep($this->crawler->getDelayBetweenRequests());

            return;
        }

        $robots = new CrawlerRobots(
            $response->getHeaders(),
            $body,
            $this->crawler->mustRespectRobots()
        );

        $crawlUrl = $this->crawler->getCrawlQueue()->getUrlById($index);

        if ($this->crawler->mayExecuteJavaScript()) {
            try {
                $body = $this->getBodyAfterExecutingJavaScript($crawlUrl->url);
            } catch (ProcessFailedException $exception) {
                $request = new Request('GET', $crawlUrl->url);
                $exception = new RequestException($exception->getMessage(), $request);
                $crawlUrl = $this->crawler->getCrawlQueue()->getUrlById($index);

                $this->crawler->getCrawlObservers()->crawlFailed($crawlUrl, $exception);

                usleep($this->crawler->getDelayBetweenRequests());

                return;
            }

            $response = $response->withBody(Utils::streamFor($body));
        }

        $responseWithCachedBody = ResponseWithCachedBody::fromGuzzlePsr7Response($response);
        $responseWithCachedBody->setCachedBody($body);

        if ($robots->mayIndex()) {
            $this->handleCrawled($responseWithCachedBody, $crawlUrl);
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
        $originalUrl = $crawlUrl->url;

        $this->urlParser->addFromHtml($body, $baseUrl, $originalUrl);

        usleep($this->crawler->getDelayBetweenRequests());
    }

    protected function getBaseUrl(ResponseInterface $response, CrawlUrl $crawlUrl): UriInterface
    {
        $redirectHistory = $response->getHeader(RedirectMiddleware::HISTORY_HEADER);

        if (empty($redirectHistory)) {
            return $crawlUrl->url;
        }

        return new Uri(end($redirectHistory));
    }

    protected function handleCrawled(ResponseInterface $response, CrawlUrl $crawlUrl): void
    {
        $this->crawler->getCrawlObservers()->crawled($crawlUrl, $response);
    }

    protected function getBody(ResponseInterface $response): string
    {
        $contentType = $response->getHeaderLine('Content-Type');

        if (! $this->isMimetypeAllowedToParse($contentType)) {
            return '';
        }

        return $this->convertBodyToString($response->getBody(), $this->crawler->getMaximumResponseSize());
    }

    protected function convertBodyToString(StreamInterface $bodyStream, $readMaximumBytes = 1024 * 1024 * 2): string
    {
        if ($bodyStream->isSeekable()) {
            $bodyStream->rewind();
        }

        $body = '';

        $chunksToRead = $readMaximumBytes < 512 ? $readMaximumBytes : 512;

        for ($bytesRead = 0; $bytesRead < $readMaximumBytes; $bytesRead += $chunksToRead) {
            try {
                $newDataRead = $bodyStream->read($chunksToRead);
            } catch (Exception $exception) {
                $newDataRead = null;
            }

            if (! $newDataRead) {
                break;
            }

            $body .= $newDataRead;
        }

        return $body;
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
