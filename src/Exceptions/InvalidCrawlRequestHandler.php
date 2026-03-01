<?php

namespace Spatie\Crawler\Exceptions;

use RuntimeException;

class InvalidCrawlRequestHandler extends RuntimeException
{
    public static function doesNotExtendBaseClass(string $handlerClass, string $baseClass): self
    {
        return new self("`{$handlerClass}` is not a valid handler class. A valid handler class should extend `{$baseClass}`.");
    }
}
