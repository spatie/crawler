<?php

namespace Spatie\Crawler\Test\TestClasses;

class Log
{
    private const path = __DIR__ . '/../temp/crawledUrls.txt';

    public static function putContents(string $text): void
    {
        file_put_contents(static::path, $text . PHP_EOL, FILE_APPEND);
    }

    public static function getContents(): string
    {
        return file_get_contents(static::path);
    }

    public static function reset(): void
    {
        file_put_contents(static::path, 'start log' . PHP_EOL);
    }
}
