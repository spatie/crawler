<?php

namespace Spatie\Crawler;

use Generator;
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\UriInterface;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use Symfony\Component\DomCrawler\Link;

class Crawler
{
    /** @var \GuzzleHttp\Client */
    protected $client;

    /** @var UriInterface */
    protected $baseUrl;

    /** @var \Spatie\Crawler\CrawlObserver */
    protected $crawlObserver;

    /** @var \Spatie\Crawler\CrawlProfile */
    protected $crawlProfile;

    /** @var int */
    protected $concurrency;

    /** @var \Spatie\Crawler\CrawlQueue */
    protected $crawlQueue;

    /**
     * @param array $clientOptions
     *
     * @return static
     */
    public static function create(array $clientOptions = [])
    {
        $hasClientOpts = (bool) count($clientOptions);
        $client = new Client($hasClientOpts ? $clientOptions : [
                RequestOptions::COOKIES => true,
                RequestOptions::CONNECT_TIMEOUT => 10,
                RequestOptions::TIMEOUT => 10,
                RequestOptions::ALLOW_REDIRECTS => false,
            ]);

        return new static($client);
    }

    public function __construct(Client $client, int $concurrency = 10)
    {
        $this->client = $client;

        $this->concurrency = $concurrency;

        $this->crawlProfile = new CrawlAllUrls();

        $this->crawlQueue = new CrawlQueue();
    }

    /**
     * @param int $concurrency
     *
     * @return $this
     */
    public function setConcurrency(int $concurrency)
    {
        $this->concurrency = $concurrency;

        return $this;
    }

    /**
     * @param \Spatie\Crawler\CrawlObserver $crawlObserver
     *
     * @return $this
     */
    public function setCrawlObserver(CrawlObserver $crawlObserver)
    {
        $this->crawlObserver = $crawlObserver;

        return $this;
    }

    /**
     * @param \Spatie\Crawler\CrawlProfile $crawlProfile
     *
     * @return $this
     */
    public function setCrawlProfile(CrawlProfile $crawlProfile)
    {
        $this->crawlProfile = $crawlProfile;

        return $this;
    }

    /**
     * @param UriInterface|string $baseUrl
     */
    public function startCrawling($baseUrl)
    {
        if (! $baseUrl instanceof UriInterface) {
            $baseUrl = new Uri($baseUrl);
        }

        $this->baseUrl = $baseUrl;

        $crawlUrl = CrawlUrl::create($baseUrl);

        $this->crawlQueue->add($crawlUrl);

        $this->startCrawlingQueue();

        $this->crawlObserver->finishedCrawling();
    }

    protected function startCrawlingQueue()
    {
        while ($this->crawlQueue->hasPendingUrls()) {
            $pool = new Pool($this->client, $this->getCrawlRequests(), [
                'concurrency' => $this->concurrency,
                'options' => $this->client->getConfig(),
                'fulfilled' => function (ResponseInterface $response, int $index) {
                    $this->handleResponse($response, $index);

                    $crawlUrl = $this->crawlQueue->getPendingUrlAtIndex($index);

                    if ($crawlUrl->url->getHost() !== $this->baseUrl->getHost()) {
                        return;
                    }

                    $this->addAllLinksToCrawlQueue(
                        (string) $response->getBody(),
                        $crawlUrl = $this->crawlQueue->getPendingUrlAtIndex($index)->url
                    );
                },
                'rejected' => function (RequestException $exception, int $index) {
                    $this->handleResponse($exception->getResponse(), $index);
                },
            ]);

            $promise = $pool->promise();
            $promise->wait();

            $this->crawlQueue->removeProcessedUrlsFromPending();
        }
    }

    /**
     * @param ResponseInterface|null $response
     * @param int $index
     */
    protected function handleResponse($response, int $index)
    {
        $crawlUrl = $this->crawlQueue->getPendingUrlAtIndex($index);

        $this->crawlObserver->hasBeenCrawled($crawlUrl->url, $response, $crawlUrl->foundOnUrl);
    }

    protected function getCrawlRequests(): Generator
    {
        $i = 0;
        while ($crawlUrl = $this->crawlQueue->getPendingUrlAtIndex($i)) {
            if (! $this->crawlProfile->shouldCrawl($crawlUrl->url)) {
                $i++;
                continue;
            }

            if ($this->crawlQueue->hasAlreadyBeenProcessed($crawlUrl)) {
                $i++;
                continue;
            }

            $this->crawlObserver->willCrawl($crawlUrl->url);

            $this->crawlQueue->markAsProcessed($crawlUrl);

            yield new Request('GET', $crawlUrl->url);
            $i++;
        }
    }

    protected function addAllLinksToCrawlQueue(string $html, UriInterface $foundOnUrl)
    {
        $allLinks = $this->extractAllLinks($html, $foundOnUrl);

        collect($allLinks)
            ->filter(function (UriInterface $url) {
                return in_array($url->getScheme(), ['http', 'https']);
            })
            ->map(function (UriInterface $url) {
                return $this->normalizeUrl($url);
            })
            ->filter(function (UriInterface $url) {
                return $this->crawlProfile->shouldCrawl($url);
            })
            ->reject(function ($url) {
                return $this->crawlQueue->has($url);
            })
            ->each(function (UriInterface $url) use ($foundOnUrl) {
                $this->crawlQueue->add(
                    CrawlUrl::create($url, $foundOnUrl)
                );
            });
    }

    protected function extractAllLinks(string $html, UriInterface $foundOnUrl): Collection
    {
        $domCrawler = new DomCrawler($html, $foundOnUrl);

        return collect($domCrawler->filterXpath('//a')->links())
            ->map(function (Link $link) {
                return new Uri($link->getUri());
            });
    }

    /**
     * @param UriInterface $url
     *
     * @return UriInterface
     */
    protected function normalizeUrl(UriInterface $url): UriInterface
    {
        return $url->withFragment('');
    }
}
