<?php

namespace Spatie\Crawler\UrlParsers;

use Psr\Http\Message\UriInterface;
use Spatie\Crawler\Crawler;

interface UrlParser
{
    public function __construct(Crawler $crawler);

    public function addFromHtml(string $html, UriInterface $foundOnUrl, ?UriInterface $originalUrl = null): void;
}
