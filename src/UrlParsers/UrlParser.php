<?php

namespace Spatie\Crawler\UrlParsers;

use Spatie\Crawler\ExtractedUrl;

interface UrlParser
{
    /** @return array<int, ExtractedUrl> */
    public function extractUrls(string $html, string $baseUrl): array;
}
