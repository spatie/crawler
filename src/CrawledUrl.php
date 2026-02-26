<?php

namespace Spatie\Crawler;

readonly class CrawledUrl
{
    public function __construct(
        public string $url,
        public int $status,
        public ?string $foundOnUrl = null,
        public int $depth = 0,
    ) {}
}
