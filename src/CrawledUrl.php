<?php

namespace Spatie\Crawler;

use Spatie\Crawler\Enums\ResourceType;

readonly class CrawledUrl
{
    public function __construct(
        public string $url,
        public int $status,
        public ?string $foundOnUrl = null,
        public int $depth = 0,
        public ResourceType $resourceType = ResourceType::Link,
    ) {}
}
