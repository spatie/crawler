<?php

namespace Spatie\Crawler;

use Spatie\Crawler\Enums\ResourceType;

class CrawlUrl
{
    public string $url;

    public ?string $foundOnUrl = null;

    public ?string $linkText = null;

    public int $depth;

    public mixed $id = null;

    public ?ResourceType $resourceType = null;

    public static function create(
        string $url,
        ?string $foundOnUrl = null,
        mixed $id = null,
        ?string $linkText = null,
        int $depth = 0,
        ?ResourceType $resourceType = null,
    ): static {
        $crawlUrl = new static;

        $crawlUrl->url = $url;
        $crawlUrl->foundOnUrl = $foundOnUrl;
        $crawlUrl->linkText = $linkText;
        $crawlUrl->depth = $depth;
        $crawlUrl->resourceType = $resourceType;

        if ($id !== null) {
            $crawlUrl->id = $id;
        }

        return $crawlUrl;
    }
}
