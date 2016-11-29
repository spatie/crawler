<?php

namespace Spatie\Crawler;

use GuzzleHttp\Client;
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
     * @var \Illuminate\Support\Collection
     */
    protected $currentPoolCrawlUrls;

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $previousPoolsCrawlUrls;

    /**
     * @var \Spatie\Crawler\CrawlObserver
     */
    protected $crawlObserver;

    /**
     * @var \Spatie\Crawler\CrawlProfile
     */
    protected $crawlProfile;

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

    /**
     * @param \GuzzleHttp\Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;

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
        if (!$baseUrl instanceof Url) {
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
            fwrite(STDERR, 'start new pool');
            $pool = new Pool($this->client, $this->getRequests(), [
                'concurrency' => 5,
                'fulfilled' => function (ResponseInterface $response, $index) {
                    $url = $this->currentPoolCrawlUrls[$index]->url;

                    $this->crawlObserver->hasBeenCrawled($url, $response);

                    $this->currentPoolCrawlUrls[$index]->status = CrawlUrl::STATUS_HAS_BEEN_CRAWLED;

                    $this->addAllLinksToCurrentPool((string)$response->getBody());
                },
                'rejected' => function ($reason, $index) {
                    echo 'still to implement';
                },
            ]);

            $promise = $pool->promise();
            $promise->wait();
            fwrite(STDERR, 'Pool done');
            $this->preparePools();
        }
    }

    public function getRequests()
    {
        $i = 0;
        while (isset($this->currentPoolCrawlUrls[$i])) {

            $crawlUrl = $this->currentPoolCrawlUrls[$i];

            if (!$this->crawlProfile->shouldCrawl($crawlUrl->url)) {
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
            fwrite(STDERR, "yielding new request for {$crawlUrl->url}" . PHP_EOL);
            yield new Request('GET', $crawlUrl->url);
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

    /**
     * Crawl all links in the given html.
     *
     * @param string $html
     */
    protected function addAllLinksToCurrentPool($html)
    {
        $allLinks = $this->getAllLinks($html);

        collect($allLinks)
            ->filter(function (Url $url) {
                return !$url->isEmailUrl();
            })
            ->filter(function (Url $url) {
                return !$url->isJavascript();
            })
            ->map(function (Url $url) {
                return $this->normalizeUrl($url);
            })
            ->filter(function (Url $url) {
                return $this->crawlProfile->shouldCrawl($url);
            })
            ->reject(function (Url $url) {
                return $this->isAlreadyRegistered($url);
            })
            ->each(function (Url $url) {
                $crawlUrl = CrawlUrl::create($url);

                $this->currentPoolCrawlUrls->push($crawlUrl);
            });
    }

    /**
     * Get all links in the given html.
     *
     * @param string $html
     *
     * @return \Spatie\Crawler\Url[]
     */
    protected function getAllLinks($html)
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
     *
     * @param \Spatie\Crawler\Url $url
     *
     * @return bool
     */
    protected function hasAlreadyCrawled(Url $url)
    {
        $alreadyCrawled = $this->currentPoolCrawlUrls
            ->merge($this->previousPoolsCrawlUrls)
            ->filter(function (CrawlUrl $crawlUrl) {
                return $crawlUrl->status === CrawlUrl::STATUS_HAS_BEEN_CRAWLED;
            });

        foreach ($alreadyCrawled as $crawledUrl) {
            if ((string)$crawledUrl === (string)$url) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the crawled has already crawled the given url.
     *
     * @param \Spatie\Crawler\Url $url
     *
     * @return bool
     */
    protected function isBeingCrawled(Url $url)
    {
        $currentlyCrawling = $this->currentPoolCrawlUrls->filter(function (CrawlUrl $crawlUrl) {
            return $crawlUrl->status === CrawlUrl::STATUS_BUSY_CRAWLING;
        });

        foreach ($currentlyCrawling as $crawledUrl) {
            if ((string)$crawledUrl->url === (string)$url) {
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

    public function isAlreadyRegistered(Url $url)
    {
        foreach ([$this->currentPoolCrawlUrls, $this->previousPoolsCrawlUrls] as $crawlUrls) {
            foreach ($crawlUrls as $crawledUrl) {
                if ((string)$crawledUrl->url === (string)$url) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function preparePools()
    {
        $crawledUrls = $this->currentPoolCrawlUrls->filter(function (CrawlUrl $crawlUrl) {
            return $crawlUrl->status != CrawlUrl::STATUS_NOT_YET_CRAWLED;
        });

        foreach ($crawledUrls as $crawlUrl) {
            $this->previousPoolsCrawlUrls->push($crawlUrl);
        }

        $this->currentPoolCrawlUrls = $this->currentPoolCrawlUrls->filter(function (CrawlUrl $crawlUrl) {
            return $crawlUrl->status === CrawlUrl::STATUS_NOT_YET_CRAWLED;
        });

//        die("nexturl" . print_r($this->currentPoolCrawlUrls, true));
    }
}
