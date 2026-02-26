<?php

namespace Spatie\Crawler\Throttlers;

interface Throttle
{
    public function sleep(): void;

    public function recordResponseTime(float $seconds): void;
}
