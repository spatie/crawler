<?php

namespace Spatie\Crawler;

class CrawlUrl
{
    public string $url;

    public ?string $foundOnUrl = null;

    public ?string $linkText = null;

    public int $depth;

    protected mixed $id = null;

    public static function create(
        string $url,
        ?string $foundOnUrl = null,
        mixed $id = null,
        ?string $linkText = null,
        int $depth = 0,
    ): static {
        $crawlUrl = new static;

        $crawlUrl->url = $url;
        $crawlUrl->foundOnUrl = $foundOnUrl;
        $crawlUrl->linkText = $linkText;
        $crawlUrl->depth = $depth;

        if ($id !== null) {
            $crawlUrl->setId($id);
        }

        return $crawlUrl;
    }

    public function getId(): mixed
    {
        return $this->id;
    }

    public function setId(mixed $id): void
    {
        $this->id = $id;
    }
}
