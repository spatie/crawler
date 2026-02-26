<?php

namespace Spatie\Crawler;

class CrawledUrl
{
    public function __construct(
        public readonly string $url,
        public readonly int $status,
        public readonly ?string $foundOnUrl = null,
        public readonly int $depth = 0,
    ) {}
}
