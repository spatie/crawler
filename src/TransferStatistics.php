<?php

namespace Spatie\Crawler;

use GuzzleHttp\TransferStats;

readonly class TransferStatistics
{
    private function __construct(
        protected TransferStats $stats,
    ) {}

    public static function fromTransferStats(TransferStats $stats): self
    {
        return new self($stats);
    }

    public function transferTimeInMs(): ?float
    {
        return $this->secondsToMs($this->getHandlerStat('total_time'));
    }

    public function connectionTimeInMs(): ?float
    {
        return $this->secondsToMs($this->getHandlerStat('connect_time'));
    }

    public function dnsLookupTimeInMs(): ?float
    {
        return $this->secondsToMs($this->getHandlerStat('namelookup_time'));
    }

    public function tlsHandshakeTimeInMs(): ?float
    {
        return $this->secondsToMs($this->getHandlerStat('appconnect_time'));
    }

    public function timeToFirstByteInMs(): ?float
    {
        return $this->secondsToMs($this->getHandlerStat('starttransfer_time'));
    }

    public function redirectTimeInMs(): ?float
    {
        return $this->secondsToMs($this->getHandlerStat('redirect_time'));
    }

    public function effectiveUri(): string
    {
        return (string) $this->stats->getEffectiveUri();
    }

    public function primaryIp(): ?string
    {
        return $this->getHandlerStat('primary_ip');
    }

    public function downloadSpeedInBytesPerSecond(): ?float
    {
        return $this->getHandlerStat('speed_download');
    }

    public function requestSizeInBytes(): ?int
    {
        $value = $this->getHandlerStat('request_size');

        return $value !== null ? (int) $value : null;
    }

    private function getHandlerStat(string $key): mixed
    {
        $value = $this->stats->getHandlerStat($key);

        if ($value === null || $value === 0 || $value === 0.0 || $value === '') {
            return null;
        }

        return $value;
    }

    private function secondsToMs(?float $seconds): ?float
    {
        if ($seconds === null) {
            return null;
        }

        return round($seconds * 1000, 3);
    }
}
