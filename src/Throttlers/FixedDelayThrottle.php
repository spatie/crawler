<?php

namespace Spatie\Crawler\Throttlers;

class FixedDelayThrottle implements Throttle
{
    public function __construct(protected int $delayMs) {}

    public function sleep(): void
    {
        usleep($this->delayMs * 1000);
    }

    public function recordResponseTime(float $seconds): void
    {
        // Fixed delay ignores response times
    }
}
