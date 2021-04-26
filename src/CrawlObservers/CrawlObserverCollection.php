<?php

namespace Spatie\Crawler\CrawlObservers;

use ArrayAccess;
use GuzzleHttp\Exception\RequestException;
use Iterator;
use Psr\Http\Message\ResponseInterface;
use Spatie\Crawler\CrawlUrl;

class CrawlObserverCollection implements ArrayAccess, Iterator
{
    protected int $position;

    public function __construct(protected array $observers = [])
    {
        $this->position = 0;
    }

    public function addObserver(CrawlObserver $observer): void
    {
        $this->observers[] = $observer;
    }

    public function crawled(CrawlUrl $crawlUrl, ResponseInterface $response): void
    {
        foreach ($this->observers as $crawlObserver) {
            $crawlObserver->crawled(
                $crawlUrl->url,
                $response,
                $crawlUrl->foundOnUrl
            );
        }
    }

    public function crawlFailed(CrawlUrl $crawlUrl, RequestException $exception): void
    {
        foreach ($this->observers as $crawlObserver) {
            $crawlObserver->crawlFailed(
                $crawlUrl->url,
                $exception,
                $crawlUrl->foundOnUrl
            );
        }
    }

    public function current()
    {
        return $this->observers[$this->position];
    }

    public function offsetGet($offset)
    {
        return $this->observers[$offset] ?? null;
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->observers[] = $value;
        } else {
            $this->observers[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->observers[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->observers[$offset]);
    }

    public function next()
    {
        $this->position++;
    }

    public function key()
    {
        return $this->position;
    }

    public function valid()
    {
        return isset($this->observers[$this->position]);
    }

    public function rewind()
    {
        $this->position = 0;
    }
}
