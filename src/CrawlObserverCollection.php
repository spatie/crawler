<?php

namespace Spatie\Crawler;

use ArrayAccess;
use GuzzleHttp\Exception\RequestException;
use Iterator;
use Psr\Http\Message\ResponseInterface;

class CrawlObserverCollection implements ArrayAccess, Iterator
{
    /** @var \Spatie\Crawler\CrawlObserver[] */
    protected $observers;

    /** @var int */
    protected $position;

    public function __construct(array $observers = [])
    {
        $this->observers = $observers;

        $this->position = 0;
    }

    public function addObserver(CrawlObserver $observer)
    {
        $this->observers[] = $observer;
    }

    public function crawled(CrawlUrl $crawlUrl, ResponseInterface $response)
    {
        foreach ($this->observers as $crawlObserver) {
            $crawlObserver->crawled(
                $crawlUrl->url,
                $response,
                $crawlUrl->foundOnUrl
            );
        }
    }

    public function crawlFailed(CrawlUrl $crawlUrl, RequestException $exception)
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
        return isset($this->observers[$offset]) ? $this->observers[$offset] : null;
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
