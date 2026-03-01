<?php

namespace Spatie\Crawler\Concerns;

trait HasCrawlLimits
{
    protected int $totalUrlCount = 0;

    protected int $currentUrlCount = 0;

    protected ?int $totalCrawlLimit = null;

    protected ?int $currentCrawlLimit = null;

    protected ?int $startedAt = null;

    protected int $executionTime = 0;

    protected ?int $totalExecutionTimeLimit = null;

    protected ?int $currentExecutionTimeLimit = null;

    public function limit(int $totalCrawlLimit): self
    {
        $this->totalCrawlLimit = $totalCrawlLimit;

        return $this;
    }

    public function limitPerExecution(int $currentCrawlLimit): self
    {
        $this->currentCrawlLimit = $currentCrawlLimit;

        return $this;
    }

    public function timeLimit(int $totalExecutionTimeLimitInSeconds): self
    {
        $this->totalExecutionTimeLimit = $totalExecutionTimeLimitInSeconds;

        return $this;
    }

    public function timeLimitPerExecution(int $currentExecutionTimeLimitInSeconds): self
    {
        $this->currentExecutionTimeLimit = $currentExecutionTimeLimitInSeconds;

        return $this;
    }

    public function getTotalCrawlLimit(): ?int
    {
        return $this->totalCrawlLimit;
    }

    public function getTotalCrawlCount(): int
    {
        return $this->totalUrlCount;
    }

    public function getCurrentCrawlLimit(): ?int
    {
        return $this->currentCrawlLimit;
    }

    public function getCurrentCrawlCount(): int
    {
        return $this->currentUrlCount;
    }

    public function getTotalExecutionTimeLimit(): ?int
    {
        return $this->totalExecutionTimeLimit;
    }

    public function getTotalExecutionTime(): int
    {
        return $this->executionTime + $this->getCurrentExecutionTime();
    }

    public function getCurrentExecutionTimeLimit(): ?int
    {
        return $this->currentExecutionTimeLimit;
    }

    public function getCurrentExecutionTime(): int
    {
        if (is_null($this->startedAt)) {
            return 0;
        }

        return time() - $this->startedAt;
    }

    public function reachedCrawlLimits(): bool
    {
        $totalCrawlLimit = $this->getTotalCrawlLimit();
        if (! is_null($totalCrawlLimit) && $this->getTotalCrawlCount() >= $totalCrawlLimit) {
            return true;
        }

        $currentCrawlLimit = $this->getCurrentCrawlLimit();
        if (! is_null($currentCrawlLimit) && $this->getCurrentCrawlCount() >= $currentCrawlLimit) {
            return true;
        }

        return false;
    }

    public function reachedTimeLimits(): bool
    {
        $totalExecutionTimeLimit = $this->getTotalExecutionTimeLimit();
        if (! is_null($totalExecutionTimeLimit) && $this->getTotalExecutionTime() >= $totalExecutionTimeLimit) {
            return true;
        }

        $currentExecutionTimeLimit = $this->getCurrentExecutionTimeLimit();
        if (! is_null($currentExecutionTimeLimit) && $this->getCurrentExecutionTime() >= $currentExecutionTimeLimit) {
            return true;
        }

        return false;
    }
}
