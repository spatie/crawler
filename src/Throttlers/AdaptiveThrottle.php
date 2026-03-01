<?php

namespace Spatie\Crawler\Throttlers;

class AdaptiveThrottle implements Throttle
{
    protected float $currentDelayMs;

    public function __construct(
        protected int $minDelayMs = 50,
        protected int $maxDelayMs = 5000,
    ) {
        $this->currentDelayMs = $this->minDelayMs;
    }

    public function sleep(): void
    {
        usleep((int) ($this->currentDelayMs * 1000));
    }

    public function recordResponseTime(float $seconds): void
    {
        $latencyMs = $seconds * 1000;

        $this->currentDelayMs = max(
            $this->minDelayMs,
            min($this->maxDelayMs, ($this->currentDelayMs + $latencyMs) / 2),
        );
    }
}
