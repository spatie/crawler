<?php

namespace Spatie\Crawler;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Spatie\Crawler\Exceptions\InvalidBaseUrl;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

class SiteCrawler
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
    protected $crawledUrls;

    /**
     * @var \Spatie\Crawler\CrawlObserver
     */
    protected $observer;

    /**
     * @var \Spatie\Crawler\CrawlProfile
     */
    protected $crawlProfile;

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

        $this->crawledUrls = collect();
    }

    /**
     * Set the base url.
     *
     * @param string $baseUrl
     *
     * @return $this
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    /**
     * Set the crawl observer.
     *
     * @param \Spatie\Crawler\CrawlObserver $observer
     *
     * @return $this
     */
    public function setObserver(CrawlObserver $observer)
    {
        $this->observer = $observer;

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
     * @param \Spatie\Crawler\Url $baseUrl
     *
     * @throws \Spatie\Crawler\Exceptions\InvalidBaseUrl
     */
    public function startCrawling(Url $baseUrl)
    {
        if ($baseUrl->isRelative()) {
            throw new InvalidBaseUrl();
        }

        $this->baseUrl = $baseUrl;

        $this->crawlUrl($baseUrl);

        $this->observer->finishedCrawling();
    }

    /**
     * Crawl the given url.
     *
     * @param \Spatie\Crawler\Url $url
     */
    protected function crawlUrl(Url $url)
    {
        if (! $this->crawlProfile->shouldCrawl($url)) {
            return;
        }

        if ($this->hasAlreadyCrawled($url)) {
            return;
        }

        $this->observer->willCrawl($url);

        try {
            $response = $this->client->request('GET', (string) $url);
        } catch (RequestException $exception) {
            $response = $exception->getResponse();
        }
        $this->observer->haveCrawled($url, $response);

        $this->crawledUrls->push($url);

        if ($url->host === $this->baseUrl->host) {
            $this->crawlAllLinks($response->getBody()->getContents());
        }
    }

    /**
     * Crawl all links in the given html.
     *
     * @param string $html
     */
    protected function crawlAllLinks($html)
    {
        $allLinks = $this->getAllLinks($html);

        collect($allLinks)
            ->filter(function (Url $url) {
                return ! $url->isEmailUrl();
            })
            ->map(function (Url $url) {
                return $this->normalizeUrl($url);
            })
            ->filter(function (Url $url) {
                return $this->crawlProfile->shouldCrawl($url);
            })
            ->map(function (Url $url) {
                $this->crawlUrl($url);
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
        $crawler = new DomCrawler($html);

        return collect($crawler->filterXpath('//a')->extract(['href']))->map(function ($url) {
            return Url::create($url);
        });
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
        foreach ($this->crawledUrls as $crawledUrl) {
            if ((string) $crawledUrl == (string) $url) {
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
            return $url
                ->setScheme($this->baseUrl->scheme)
                ->setHost($this->baseUrl->host);
        }

        if ($url->isProtocolIndependent()) {
            $url->setScheme($this->baseUrl->scheme);
        }

        return $url->removeFragment();
    }
}
