<?php

namespace Spatie\Crawler;

use Psr\Http\Message\UriInterface;

class CrawlUrl
{
    public UriInterface $url;

    public ?UriInterface $foundOnUrl = null;

    public ?string $linkText = null;

    protected mixed $id;

    public static function create(
        UriInterface $url,
        ?UriInterface $foundOnUrl = null,
        $id = null,
        ?string $linkText = null,
    ): static {
        $static = new static($url, $foundOnUrl, linkText: $linkText);

        if ($id !== null) {
            $static->setId($id);
        }

        return $static;
    }

    protected function __construct(UriInterface $url, $foundOnUrl = null, $linkText = null)
    {
        $this->url = $url;

        $this->foundOnUrl = $foundOnUrl;

        $this->linkText = $linkText;
    }

    public function getId(): mixed
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }
}
