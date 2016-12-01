<?php

namespace Spatie\Crawler;

use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
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
    private $concurrency;

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $currentPoolCrawlUrls;

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $previousPoolsCrawlUrls;

    /**
     * @return static
     */
    public static function create()
    {
        $client = new Client([
            RequestOptions::ALLOW_REDIRECTS => false,
            RequestOptions::COOKIES => true,
        ]);

        return new static($client);
    }

    public function __construct(Client $client, $concurrency = 10)
    {
        $this->client = $client;

        $this->concurrency = $concurrency;

        $this->crawlProfile = new CrawlAllUrls();

        $this->currentPoolCrawlUrls = collect();

        $this->previousPoolsCrawlUrls = collect();
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

        $this->currentPoolCrawlUrls->push($crawlUrl);

        $this->startCrawlingCurrentPool();

        $this->crawlObserver->finishedCrawling();
    }

    /**
     * Crawl urls in the currentPool.
     */
    protected function startCrawlingCurrentPool()
    {
        while ($this->currentPoolCrawlUrls->count() > 0) {
            $pool = new Pool($this->client, $this->getRequests(), [
                'concurrency' => $this->concurrency,
                'fulfilled' => function (ResponseInterface $response, int $index) {
                    $this->handleResponse($response, $index);

                    $this->addAllLinksToCurrentPool((string) $response->getBody());
                },
                'rejected' => function (ClientException $exception, int $index) {
                    $this->handleResponse($exception->getResponse(), $index, $exception);
                },
            ]);

            $promise = $pool->promise();
            $promise->wait();

            $this->preparePoolsForNextLoop();
        }
    }

    public function handleResponse(ResponseInterface $response, int $index, ClientException $exception = null)
    {
        $url = $this->currentPoolCrawlUrls[$index]->url;

        $this->crawlObserver->hasBeenCrawled($url, $response, $exception);

        $this->currentPoolCrawlUrls[$index]->status = CrawlUrl::STATUS_HAS_BEEN_CRAWLED;
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

            if ($this->isBeingCrawled($crawlUrl->url)) {
                $i++;
                continue;
            }

            if ($this->hasAlreadyCrawled($crawlUrl->url)) {
                $i++;
                continue;
            }

            $this->crawlObserver->willCrawl($crawlUrl->url);

            $crawlUrl->status = CrawlUrl::STATUS_BUSY_CRAWLING;

            yield new Request('GET', (string) $crawlUrl->url);
            $i++;
        }
    }

    /**
     * @return \Spatie\Crawler\CrawlUrl|null
     */
    public function getNextCrawlUrl()
    {
        return $this->currentPoolCrawlUrls->filter(function (CrawlUrl $crawlUrl) {
            return $crawlUrl->status === CrawlUrl::STATUS_NOT_YET_CRAWLED;
        })->first();
    }

    protected function addAllLinksToCurrentPool(string $html)
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
            ->each(function (Url $url) {
                if (! $this->isAlreadyRegistered($url)) {
                    $crawlUrl = CrawlUrl::create($url);

                    $this->currentPoolCrawlUrls->push($crawlUrl);
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
     * Determine if the crawled has already crawled the given url.
     */
    protected function hasAlreadyCrawled(Url $url): bool
    {
        $alreadyCrawled = $this->currentPoolCrawlUrls
            ->merge($this->previousPoolsCrawlUrls)
            ->filter(function (CrawlUrl $crawlUrl) {
                return $crawlUrl->status === CrawlUrl::STATUS_HAS_BEEN_CRAWLED;
            });

        foreach ($alreadyCrawled as $crawledUrl) {
            if ((string) $crawledUrl->url === (string) $url) {
                return true;
            }
        }

        return false;
    }

    /*
     * Determine if the crawled has already crawled the given url.
     */
    protected function isBeingCrawled(Url $url): bool
    {
        $currentlyCrawling = $this->currentPoolCrawlUrls->filter(function (CrawlUrl $crawlUrl) {
            return $crawlUrl->status === CrawlUrl::STATUS_BUSY_CRAWLING;
        });

        foreach ($currentlyCrawling as $crawledUrl) {
            if ((string) $crawledUrl->url === (string) $url) {
                return true;
            }
        }

        return false;
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

    public function isAlreadyRegistered(Url $url): bool
    {
        foreach ([$this->currentPoolCrawlUrls, $this->previousPoolsCrawlUrls] as $crawlUrls) {
            foreach ($crawlUrls as $crawledUrl) {
                if ((string) $crawledUrl->url === (string) $url) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function preparePoolsForNextLoop()
    {
        $crawledUrls = $this->currentPoolCrawlUrls->filter(function (CrawlUrl $crawlUrl) {
            return $crawlUrl->status != CrawlUrl::STATUS_NOT_YET_CRAWLED;
        });

        foreach ($crawledUrls as $crawlUrl) {
            $this->previousPoolsCrawlUrls->push($crawlUrl);
        }

        $this->currentPoolCrawlUrls = $this->currentPoolCrawlUrls->filter(function (CrawlUrl $crawlUrl) {
            return $crawlUrl->status === CrawlUrl::STATUS_NOT_YET_CRAWLED;
        })->values();
    }
}
