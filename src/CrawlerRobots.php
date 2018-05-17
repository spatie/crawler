<?php

namespace Spatie\Crawler;

use Psr\Http\Message\ResponseInterface;
use Spatie\Robots\RobotsHeaders;
use Spatie\Robots\RobotsMeta;

class CrawlerRobots
{
    /** @var \Spatie\Robots\RobotsHeaders */
    protected $robotsHeaders;

    /** @var \Spatie\Robots\RobotsMeta */
    protected $robotsMeta;

    /** @var bool */
    private $mustRespectRobots;

    public function __construct(ResponseInterface $response, bool $mustRespectRobots)
    {
        $this->robotsHeaders = RobotsHeaders::create($response->getHeaders());

        $this->robotsMeta = RobotsMeta::create((string) $response->getBody());

        $this->mustRespectRobots = $mustRespectRobots;
    }

    public function mayIndex(): bool
    {
        if (! $this->mustRespectRobots) {
            return true;
        }

        return $this->robotsHeaders->mayIndex()
            && $this->robotsMeta->mayIndex();
    }

    public function mayFollow(): bool
    {
        if (! $this->mustRespectRobots) {
            return true;
        }

        return $this->robotsHeaders->mayFollow()
            && $this->robotsMeta->mayFollow();
    }
}
