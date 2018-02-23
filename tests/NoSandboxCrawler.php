<?php

namespace Spatie\Crawler\Test;

use Tree\Node\Node;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Uri;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlUrl;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlSubdomains;
use Symfony\Component\DomCrawler\Link;
use Psr\Http\Message\ResponseInterface;
use Tightenco\Collect\Support\Collection;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

class NoSandboxCrawler extends Crawler
{
    /**
     * @param \Psr\Http\Message\UriInterface|string $baseUrl
     */
    public function startCrawling($baseUrl)
    {
        if (! $baseUrl instanceof UriInterface) {
            $baseUrl = new Uri($baseUrl);
        }

        if ($baseUrl->getScheme() === '') {
            $baseUrl = $baseUrl->withScheme('http');
        }

        if ($baseUrl->getPath() === '') {
            $baseUrl = $baseUrl->withPath('/');
        }

        $this->baseUrl = $baseUrl;

        $crawlUrl = CrawlUrl::create($this->baseUrl);

        $this->addToCrawlQueue($crawlUrl);

        $this->depthTree = new Node((string) $this->baseUrl);

        $this->startCrawlingQueue();

        foreach ($this->crawlObservers as $crawlObserver) {
            $crawlObserver->finishedCrawling();
        }
    }

    protected function startCrawlingQueue()
    {
        while ($this->crawlQueue->hasPendingUrls()) {
            $pool = new Pool($this->client, $this->getCrawlRequests(), [
                'concurrency' => $this->concurrency,
                'options' => $this->client->getConfig(),
                'fulfilled' => function (ResponseInterface $response, int $index) {
                    $crawlUrl = $this->crawlQueue->getUrlById($index);
                    $this->handleResponse($response, $crawlUrl);

                    if (! $this->crawlProfile instanceof CrawlSubdomains) {
                        if ($crawlUrl->url->getHost() !== $this->baseUrl->getHost()) {
                            return;
                        }
                    }

                    $body = $this->convertBodyToString($response->getBody(), $this->maximumResponseSize);

                    $this->addAllLinksToCrawlQueue(
                        $body,
                        $crawlUrl->url
                    );
                },
                'rejected' => function (RequestException $exception, int $index) {
                    $this->handleResponse(
                        $exception->getResponse(),
                        $this->crawlQueue->getUrlById($index)
                    );
                },
            ]);

            $promise = $pool->promise();
            $promise->wait();
        }
    }

    protected function addAllLinksToCrawlQueue(string $html, UriInterface $foundOnUrl)
    {
        $allLinks = $this->extractAllLinks($html, $foundOnUrl);

        collect($allLinks)
            ->filter(function (UriInterface $url) {
                return $this->hasCrawlableScheme($url);
            })
            ->map(function (UriInterface $url) {
                return $this->normalizeUrl($url);
            })
            ->filter(function (UriInterface $url) {
                return $this->crawlProfile->shouldCrawl($url);
            })
            ->reject(function (UriInterface $url) {
                return $this->crawlQueue->has($url);
            })
            ->each(function (UriInterface $url) use ($foundOnUrl) {
                $node = $this->addtoDepthTree($this->depthTree, $url, $foundOnUrl);

                if (! $this->shouldCrawl($node)) {
                    return;
                }

                if ($this->maximumCrawlCountReached()) {
                    return;
                }

                $crawlUrl = CrawlUrl::create($url, $foundOnUrl);

                $this->addToCrawlQueue($crawlUrl);
            });
    }

    protected function extractAllLinks(string $html, UriInterface $foundOnUrl): Collection
    {
        if ($this->executeJavaScript) {
            $html = $this->getBodyAfterExecutingJavaScript($foundOnUrl);
        }

        $domCrawler = new DomCrawler($html, $foundOnUrl);

        return collect($domCrawler->filterXpath('//a')->links())
            ->map(function (Link $link) {
                try {
                    return new Uri($link->getUri());
                } catch (InvalidArgumentException $exception) {
                    return;
                }
            })
            ->filter();
    }

    protected function getBodyAfterExecutingJavaScript(UriInterface $foundOnUrl): string
    {
        $browsershot = $this->getBrowsershot();

        $html = getenv('TRAVIS')
            ? $browsershot->url((string) $foundOnUrl)->noSandbox()->bodyHtml()
            : $browsershot->url((string) $foundOnUrl)->bodyHtml();

        return html_entity_decode($html);
    }
}
