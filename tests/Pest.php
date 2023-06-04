<?php

use PHPUnit\Framework\Assert;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertStringNotContainsString;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\Test\TestClasses\CrawlLogger;
use Spatie\Crawler\Test\TestClasses\Log;

expect()->extend('toBeNotEmpty', function () {
    Assert::assertNotEmpty($this->value);

    return $this;
});

expect()->extend('notToBeCrawled', function () {
    $url = $this->value;

    $logMessage = "hasBeenCrawled: {$url['url']}";

    if (isset($url['foundOn'])) {
        $logMessage .= " - found on {$url['foundOn']}";
    }

    $logMessage .= PHP_EOL;

    assertStringNotContainsString(
        $logMessage,
        Log::getContents(),
        "Did find {$logMessage} in the log"
    );
});

expect()->extend('toBeCrawledOnce', function () {
    $logContent = Log::getContents();

    $url = $this->value;

    if (! isset($url['linkText'])) {
        $logContent = preg_replace('/ - link text .*/', '', $logContent);
    }

    $logMessage = "hasBeenCrawled: {$url['url']}";

    if (isset($url['foundOn'])) {
        $logMessage .= " - found on {$url['foundOn']}";
    }

    if (isset($url['linkText'])) {
        $logMessage .= " - link text {$url['linkText']}";
    }

    $logMessage .= PHP_EOL;

    assertEquals(
        1,
        substr_count($logContent, $logMessage),
        "Did not find {$logMessage} exactly one time in the log but ".substr_count($logContent, $logMessage)." times. Contents of log\n{$logContent}"
    );
});

function assertCrawledUrlCount(int $count): void
{
    $logContent = Log::getContents();

    $actualCount = substr_count($logContent, 'hasBeenCrawled');

    assertEquals($count, $actualCount, "Crawled `{$actualCount}` urls instead of the expected {$count}");
}

function skipIfTestServerIsNotRunning(): void
{
    try {
        file_get_contents('http://localhost:8080');
    } catch (Throwable $e) {
        test()->markTestSkipped('The test server is not running.');
    }
}

function createCrawler($options = []): Crawler
{
    return Crawler::create($options)
        ->setMaximumDepth(3)
        ->setCrawlObserver(new CrawlLogger());
}
