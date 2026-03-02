<?php

namespace Spatie\Crawler\Exceptions;

use RuntimeException;

class MissingJavaScriptRenderer extends RuntimeException
{
    public static function browsershotNotInstalled(): self
    {
        return new self('To execute JavaScript, install spatie/browsershot or provide a custom JavaScriptRenderer.');
    }
}
