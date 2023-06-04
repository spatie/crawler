<?php

namespace Spatie\Crawler;

use GuzzleHttp\Psr7\Uri;

class Url extends Uri
{
    public function __construct(
        protected string $link,
        protected string|null $linkText,
    ) {
        parent::__construct($link);
    }

    public function linkText(): string|null
    {
        return $this->linkText;
    }
}
