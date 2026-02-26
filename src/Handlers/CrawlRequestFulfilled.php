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
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlerRobots;
use Spatie\Crawler\CrawlProfiles\CrawlSubdomains;
use Spatie\Crawler\CrawlResponse;
use Spatie\Crawler\CrawlUrl;
use Symfony\Component\Process\Exception\ProcessFailedException;

class CrawlRequestFulfilled
{
    public function __construct(protected Crawler $crawler) {}

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

        $renderer = $this->crawler->getJavaScriptRenderer();
        if ($renderer !== null) {
            try {
                $body = $renderer->getRenderedHtml($crawlUrl->url);
            } catch (ProcessFailedException $exception) {
                $request = new Request('GET', $crawlUrl->url);
                $exception = new RequestException($exception->getMessage(), $request);

                $this->crawler->getCrawlObservers()->crawlFailed($crawlUrl, $exception);

                usleep($this->crawler->getDelayBetweenRequests());

                return;
            }

            $response = $response->withBody(Utils::streamFor($body));
        }

        $crawlResponse = new CrawlResponse(
            $response,
            $crawlUrl->foundOnUrl,
            $crawlUrl->linkText,
            $crawlUrl->depth,
        );
        $crawlResponse->setCachedBody($body);

        if ($robots->mayIndex()) {
            $this->crawler->getCrawlObservers()->crawled($crawlUrl, $crawlResponse);
        }

        if (! $this->crawler->getCrawlProfile() instanceof CrawlSubdomains) {
            $crawlHost = parse_url($crawlUrl->url, PHP_URL_HOST);
            $baseHost = parse_url($this->crawler->getBaseUrl(), PHP_URL_HOST);

            if ($crawlHost !== $baseHost) {
                return;
            }
        }

        if (! $robots->mayFollow()) {
            return;
        }

        $baseUrl = $this->getBaseUrl($response, $crawlUrl);

        $urlParser = $this->crawler->getUrlParser();
        $extractedUrls = $urlParser->extractUrls($body, $baseUrl);

        $maximumDepth = $this->crawler->getMaximumDepth();

        foreach ($extractedUrls as $url => $linkText) {
            $newDepth = $crawlUrl->depth + 1;

            if ($maximumDepth !== null && $newDepth > $maximumDepth) {
                continue;
            }

            if ($this->crawler->mustRespectRobots()) {
                $robotsTxt = $this->crawler->getRobotsTxt();

                if ($robotsTxt !== null && ! $robotsTxt->allows($url, $this->crawler->getUserAgent())) {
                    continue;
                }
            }

            $newCrawlUrl = CrawlUrl::create(
                url: $url,
                foundOnUrl: $baseUrl,
                linkText: $linkText,
                depth: $newDepth,
            );

            $this->crawler->addToCrawlQueue($newCrawlUrl);
        }

        usleep($this->crawler->getDelayBetweenRequests());
    }

    protected function getBaseUrl(ResponseInterface $response, CrawlUrl $crawlUrl): string
    {
        $redirectHistory = $response->getHeader(RedirectMiddleware::HISTORY_HEADER);

        if (empty($redirectHistory)) {
            return $crawlUrl->url;
        }

        return end($redirectHistory);
    }

    protected function getBody(ResponseInterface $response): string
    {
        $contentType = $response->getHeaderLine('Content-Type');

        if (! $this->isMimetypeAllowedToParse($contentType)) {
            return '';
        }

        return $this->convertBodyToString($response->getBody(), $this->crawler->getMaximumResponseSize());
    }

    protected function convertBodyToString(StreamInterface $bodyStream, int $readMaximumBytes = 1024 * 1024 * 2): string
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

    protected function isMimetypeAllowedToParse(string $contentType): bool
    {
        if (empty($contentType)) {
            return true;
        }

        $allowedTypes = $this->crawler->getAllowedMimeTypes();

        if (! count($allowedTypes)) {
            return true;
        }

        return array_any($allowedTypes, fn (string $allowedType) => stristr($contentType, $allowedType) !== false);
    }
}
