<?php

namespace Spatie\Crawler\CrawlQueue;

use Predis\Client;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlUrl;
use Spatie\Crawler\Exception\InvalidUrl;
use Spatie\Crawler\Exception\UrlNotFoundByIndex;

/**
 * Implementation of CrawlQueue using Redis Hashes
 */
class RedisCrawlQueue implements CrawlQueue
{
    // All known URLs, indexed by URL string.
    const URLS = 'urls';
    // Pending URLs, indexed by URL string.
    const PENDING_URLS = 'pending';

    /**
     * Redis Instance
     * @var \Predis\Client
     */
    private $redis;

    public function __construct(?Client $redis = null)
    {
        $this->redis = $redis;
        if (is_null($redis)) {
            $this->redis = new Client();
        }
    }

    public function add(CrawlUrl $url) : CrawlQueue
    {
        $urlString = (string) $url->url;

        if (!$this->has($urlString)) {
            $url->setId($urlString);

            $this->redis->hset(self::URLS, $urlString, serialize($url));
            $this->redis->hset(self::PENDING_URLS, $urlString, serialize($url));
        }

        return $this;
    }

    public function has($crawlUrl) : bool
    {
        if ($crawlUrl instanceof CrawlUrl) {
            $url = (string) $crawlUrl->url;
        } elseif ($crawlUrl instanceof UriInterface) {
            $url = (string) $crawlUrl;
        } elseif (is_string($crawlUrl)) {
            $url = $crawlUrl;
        } else {
            throw InvalidUrl::unexpectedType($crawlUrl);
        }

        return (bool) $this->redis->hexists(self::URLS, $url);
    }

    public function hasPendingUrls() : bool
    {
        return (bool) $this->redis->hlen(self::PENDING_URLS);
    }

    public function getUrlById($id) : CrawlUrl
    {
        if (!$this->has($id)) {
            throw new UrlNotFoundByIndex("Crawl url {$id} not found in hashes.");
        }
        return unserialize($this->redis->hget(self::URLS, $id));
    }

    public function getFirstPendingUrl() : ?CrawlUrl
    {
        $keys = $this->redis->hkeys(self::PENDING_URLS);

        foreach ($keys as $key) {
            return unserialize($this->redis->hget(self::PENDING_URLS, $key));
        }

        return null;
    }

    public function hasAlreadyBeenProcessed(CrawlUrl $url) : bool
    {
        $url = (string) $url->url;

        if ($this->redis->hexists(self::PENDING_URLS, $url)) {
            return false;
        }

        if ($this->redis->hexists(self::URLS, $url)) {
            return true;
        }

        return false;
    }

    public function markAsProcessed(CrawlUrl $crawlUrl)
    {
        $this->redis->hdel(self::PENDING_URLS, (string) $crawlUrl->url);
    }
}
