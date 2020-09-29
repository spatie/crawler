<?php

namespace Spatie\Crawler;

use Spatie\Robots\RobotsHeaders;
use Spatie\Robots\RobotsMeta;

class CrawlerRobots
{
    protected RobotsHeaders $robotsHeaders;

    protected RobotsMeta $robotsMeta;

    protected bool $mustRespectRobots;

    public function __construct(array $headers, string $body, bool $mustRespectRobots)
    {
        $this->robotsHeaders = RobotsHeaders::create($headers);

        $this->robotsMeta = RobotsMeta::create($body);

        $this->mustRespectRobots = $mustRespectRobots;
    }

    public function mayIndex(): bool
    {
        if (! $this->mustRespectRobots) {
            return true;
        }

        if (! $this->robotsHeaders->mayIndex()) {
            return false;
        }

        if (! $this->robotsMeta->mayIndex()) {
            return false;
        }

        return true;
    }

    public function mayFollow(): bool
    {
        if (! $this->mustRespectRobots) {
            return true;
        }

        if (! $this->robotsHeaders->mayFollow()) {
            return false;
        }

        if (! $this->robotsMeta->mayFollow()) {
            return false;
        }

        return true;
    }
}
