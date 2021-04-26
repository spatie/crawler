<?php

namespace Spatie\Crawler\Exceptions;

use RuntimeException;

class InvalidCrawlRequestHandler extends RuntimeException
{
    public static function doesNotExtendBaseClass(string $handlerClass, string $baseClass): static
    {
        return new static("`{$handlerClass} is not a valid handler class. A valid handler class should extend `{$baseClass}`.");
    }
}
