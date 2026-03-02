<?php

namespace Spatie\Crawler;

use Spatie\Crawler\Enums\ResourceType;

readonly class ExtractedUrl
{
    public function __construct(
        public string $url,
        public ?string $linkText = null,
        public ResourceType $resourceType = ResourceType::Link,
        public ?string $malformedReason = null,
    ) {}

    public function isMalformed(): bool
    {
        return $this->malformedReason !== null;
    }
}
