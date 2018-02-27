<?php

namespace Spatie\Crawler\Test;

use GuzzleHttp\Exception\RequestException;
use Spatie\Crawler\CrawlObserver;
use Psr\Http\Message\UriInterface;

class CrawlLogger implements CrawlObserver
{
    /** @var string */
    protected $observerId;

    public function __construct(string $observerId = '')
    {
        if ($observerId !== '') {
            $observerId .= ' - ';
        }

        $this->observerId = $observerId;
    }

    /**
     * Called when the crawler will crawl the url.
     *
     * @param \Psr\Http\Message\UriInterface   $url
     */
    public function willCrawl(UriInterface $url)
    {
        CrawlerTest::log("{$this->observerId}willCrawl: {$url}");
    }

    /**
     * Called when the crawler has crawled the given url.
     *
     * @param \Psr\Http\Message\UriInterface $url
     * @param \Psr\Http\Message\ResponseInterface|null $response
     * @param \Psr\Http\Message\UriInterface|null $foundOnUrl
     * @param RequestException|null $exception
     */
    public function hasBeenCrawled(
        UriInterface $url,
        $response,
        ?UriInterface $foundOnUrl = null,
        ?RequestException $exception = null
    )
    {
        $logText = "{$this->observerId}hasBeenCrawled: {$url}";

        if ((string) $foundOnUrl) {
            $logText .= " - found on {$foundOnUrl}";
        }

        CrawlerTest::log($logText);
    }

    /**
     * Called when the crawl has ended.
     */
    public function finishedCrawling()
    {
        CrawlerTest::log("{$this->observerId}finished crawling");
    }
}
