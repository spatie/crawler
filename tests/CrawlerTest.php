<?php

namespace Spatie\Crawler\Test;

use PHPUnit_Framework_TestCase;
use Spatie\Crawler\Crawler;

class CrawlerTest extends PHPUnit_Framework_TestCase
{
    /** @var logPath */
    protected static $logPath;

    public function setUp()
    {
        parent::setUp();

        static::$logPath = __DIR__ . '/temp/crawledUrls.txt';

        file_put_contents(static::$logPath, 'start log'.PHP_EOL);
    }

    /** @test */
    public function it_crawls()
    {
        Crawler::create()
            ->setCrawlObserver(new CrawlLogger())
            ->startCrawling('http://localhost:8080');
    }

    protected function assertCrawledOnce(array $urls)
    {

    }

    public static function log(string $text)
    {
        file_put_contents(static::$logPath, $text . PHP_EOL, FILE_APPEND);
    }


}