<?php

namespace Spatie\Crawler;

use GuzzleHttp\Psr7\Uri;

class Url extends Uri
{
    public function __construct(
        protected string $link,
        protected ?string $linkText,
    ) {
        parent::__construct($link);
    }

    public function linkText(): ?string
    {
        return $this->linkText;
    }
}
