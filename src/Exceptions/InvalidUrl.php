<?php

namespace Spatie\Crawler\Exceptions;

use Exception;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlUrl;

class InvalidUrl extends Exception
{
    public static function unexpectedType(mixed $url): static
    {
        $crawlUrlClass = CrawlUrl::class;
        $uriInterfaceClass = UriInterface::class;
        $givenUrlClass = is_object($url) ? get_class($url) : gettype($url);

        return new static("You passed an invalid url of type `{$givenUrlClass}`. This should be either a {$crawlUrlClass} or `{$uriInterfaceClass}`");
    }
}
