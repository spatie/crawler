<?php

namespace Spatie\Crawler;

use Spatie\Crawler\Enums\ResourceType;

class CrawlUrl
{
    public function __construct(
        public string $url,
        public ?string $foundOnUrl = null,
        public ?string $linkText = null,
        public int $depth = 0,
        public mixed $id = null,
        public ?ResourceType $resourceType = null,
    ) {}
}
