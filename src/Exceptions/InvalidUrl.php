<?php

namespace Spatie\Crawler\Exceptions;

use Exception;

class InvalidUrl extends Exception
{
    public static function unexpectedType(mixed $url): static
    {
        $givenUrlClass = is_object($url) ? get_class($url) : gettype($url);

        return new static("You passed an invalid url of type `{$givenUrlClass}`. Expected a string.");
    }
}
