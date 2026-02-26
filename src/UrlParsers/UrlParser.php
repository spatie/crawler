<?php

namespace Spatie\Crawler\UrlParsers;

interface UrlParser
{
    /** @return array<string, ?string> url => linkText */
    public function extractUrls(string $html, string $baseUrl): array;
}
