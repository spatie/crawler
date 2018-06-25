<?php

namespace Spatie\Crawler\Exception;

use RuntimeException;

class InvalidCrawlRequestHandler extends RuntimeException
{
    public static function doesNotExtendBaseClass(string $handlerClass, string $baseClass)
    {
        return new static("`{$handlerClass} is not a valid handler class. A valid handleres class should extend `{$baseClass}`.");
    }
}
