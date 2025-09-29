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
                $crawlUrl->foundOnUrl,
                $crawlUrl->linkText,
            );
        }
    }

    public function crawlFailed(CrawlUrl $crawlUrl, RequestException $exception): void
    {
        foreach ($this->observers as $crawlObserver) {
            $crawlObserver->crawlFailed(
                $crawlUrl->url,
                $exception,
                $crawlUrl->foundOnUrl,
                $crawlUrl->linkText,
            );
        }
    }

    public function current(): mixed
    {
        return $this->observers[$this->position];
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->observers[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->observers[] = $value;
        } else {
            $this->observers[$offset] = $value;
        }
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->observers[$offset]);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->observers[$offset]);
    }

    public function next(): void
    {
        $this->position++;
    }

    public function key(): mixed
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->observers[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }
}
