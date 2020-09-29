<?php

namespace Spatie\Crawler\Test;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Throwable;

class TestCase extends BaseTestCase
{
    protected static string $logPath;

    public function skipIfTestServerIsNotRunning(): void
    {
        try {
            file_get_contents('http://localhost:8080');
        } catch (Throwable $e) {
            $this->markTestSkipped('The testserver is not running.');
        }
    }

    protected function getLogContents(): string
    {
        return file_get_contents(static::$logPath);
    }

    protected function assertCrawledOnce(array $urls): void
    {
        $logContent = $this->getLogContents();

        foreach ($urls as $url) {
            $logMessage = "hasBeenCrawled: {$url['url']}";

            if (isset($url['foundOn'])) {
                $logMessage .= " - found on {$url['foundOn']}";
            }

            $logMessage .= PHP_EOL;

            $this->assertEquals(1, substr_count($logContent, $logMessage), "Did not find {$logMessage} exactly one time in the log but ".substr_count($logContent, $logMessage)." times. Contents of log\n{$logContent}");
        }
    }

    protected function assertNotCrawled(array $urls): void
    {
        $logContent = $this->getLogContents();

        foreach ($urls as $url) {
            $logMessage = "hasBeenCrawled: {$url['url']}";

            if (isset($url['foundOn'])) {
                $logMessage .= " - found on {$url['foundOn']}";
            }

            $logMessage .= PHP_EOL;

            $this->assertEquals(0, substr_count($logContent, $logMessage), "Did find {$logMessage} in the log");
        }
    }

    protected function assertCrawledUrlCount(int $count): void
    {
        $logContent = file_get_contents(static::$logPath);

        $actualCount = substr_count($logContent, 'hasBeenCrawled');

        $this->assertEquals($count, $actualCount, "Crawled `{$actualCount}` urls instead of the expected {$count}");
    }

    public function resetLog(): void
    {
        static::$logPath = __DIR__.'/temp/crawledUrls.txt';

        file_put_contents(static::$logPath, 'start log'.PHP_EOL);
    }
}
