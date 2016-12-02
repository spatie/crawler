<?php

namespace Spatie\Crawler;

use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Pool;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

class Crawler
{
    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var \Spatie\Crawler\Url;
     */
    protected $baseUrl;

    /**
     * @var \Spatie\Crawler\CrawlObserver
     */
    protected $crawlObserver;

    /**
     * @var \Spatie\Crawler\CrawlProfile
     */
    protected $crawlProfile;

    /**
     * @var int
     */
    protected $concurrency;

    /**
     * @var \Spatie\Crawler\CrawlQueue
     */
    protected $crawlQueue;

    /**
     * @param array $clientOptions
     *
     * @return static
     */
    public static function create(array $clientOptions = null)
    {
        $client = new Client($clientOptions ?? [
                RequestOptions::ALLOW_REDIRECTS => false,
                RequestOptions::COOKIES => true,
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
     * Set the crawl observer.
     *
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
     * Set the crawl profile.
     *
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
     * Start the crawling process.
     *
     * @param \Spatie\Crawler\Url|string $baseUrl
     */
    public function startCrawling($baseUrl)
    {
        if (! $baseUrl instanceof Url) {
            $baseUrl = Url::create($baseUrl);
        }

        $this->baseUrl = $baseUrl;

        $crawlUrl = CrawlUrl::create($baseUrl);

        $this->crawlQueue->add($crawlUrl);

        $this->startCrawlingCurrentPool();

        $this->crawlObserver->finishedCrawling();
    }

    /**
     * Crawl urls in the currentPool.
     */
    protected function startCrawlingCurrentPool()
    {
        while ($this->crawlQueue->hasPendingUrls()) {
            $pool = new Pool($this->client, $this->getRequests(), [
                'concurrency' => $this->concurrency,
                'fulfilled' => function (ResponseInterface $response, int $index) {
                    $this->handleResponse($response, $index);

                    $crawlUrl = $this->crawlQueue->getPendingUrlAtIndex($index);

                    if ($crawlUrl->url->host !== $this->baseUrl->host) {
                        return;
                    }

                    $this->addAllLinksToCrawlQueue(
                        (string) $response->getBody(),
                        $crawlUrl = $this->crawlQueue->getPendingUrlAtIndex($index)
                    );
                },
                'rejected' => function (RequestException $exception, int $index) {
                    $this->handleResponse($exception->getResponse(), $index);
                },
            ]);

            $promise = $pool->promise();
            $promise->wait();
        }
    }

    public function handleResponse(ResponseInterface $response, int $index)
    {
        $crawlUrl = $this->crawlQueue->getPendingUrlAtIndex($index);

        $this->crawlObserver->hasBeenCrawled($crawlUrl->url, $response, $crawlUrl->foundOnUrl);

        $this->crawlQueue->moveToProcessed($crawlUrl);
    }

    public function getRequests(): Generator
    {
        $i = 0;
        while (isset($this->currentPoolCrawlUrls[$i])) {
            $crawlUrl = $this->currentPoolCrawlUrls[$i];

            if (! $this->crawlProfile->shouldCrawl($crawlUrl->url)) {
                $i++;
                continue;
            }

            if ($this->crawlQueue->isBeingProcessed($crawlUrl->url)) {
                $i++;
                continue;
            }

            if ($this->crawlQueue->hasAlreadyBeenProcessed($crawlUrl->url)) {
                $i++;
                continue;
            }

            $this->crawlObserver->willCrawl($crawlUrl->url);

            $this->crawlQueue->moveToProcessing($crawlUrl->url);

            $crawlUrl->status = CrawlUrl::STATUS_BUSY_CRAWLING;

            yield new Request('GET', (string) $crawlUrl->url);
            $i++;
        }
    }

    protected function addAllLinksToCrawlQueue(string $html, Url $foundOnUrl)
    {
        $allLinks = $this->getAllLinks($html);

        collect($allLinks)
            ->filter(function (Url $url) {
                return ! $url->isEmailUrl();
            })
            ->filter(function (Url $url) {
                return ! $url->isJavascript();
            })
            ->map(function (Url $url) {
                return $this->normalizeUrl($url);
            })
            ->filter(function (Url $url) {
                return $this->crawlProfile->shouldCrawl($url);
            })
            ->each(function (Url $url) use ($foundOnUrl) {
                if (! $this->crawlQueue->has($url)) {
                    $crawlUrl = CrawlUrl::create($url, $foundOnUrl);

                    $this->crawlQueue->addToPending($crawlUrl);
                }
            });
    }

    /**
     * Get all links in the given html.
     *
     * @param string $html
     *
     * @return \Spatie\Crawler\Url[]
     */
    protected function getAllLinks(string $html)
    {
        $domCrawler = new DomCrawler($html);

        $allUrls = collect($domCrawler->filterXpath('//a')
            ->extract(['href']))
            ->map(function ($url) {
                return Url::create($url);
            });

        return $allUrls;
    }

    /**
     * Normalize the given url.
     *
     * @param \Spatie\Crawler\Url $url
     *
     * @return $this
     */
    protected function normalizeUrl(Url $url)
    {
        if ($url->isRelative()) {
            $url->setScheme($this->baseUrl->scheme)
                ->setHost($this->baseUrl->host)
                ->setPort($this->baseUrl->port);
        }

        if ($url->isProtocolIndependent()) {
            $url->setScheme($this->baseUrl->scheme);
        }

        return $url->removeFragment();
    }
}
