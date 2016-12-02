<?php

namespace Spatie\Crawler\Test;

use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlProfile;
use Spatie\Crawler\Url;

class CrawlerTest extends TestCase
{
    /** @var logPath */
    protected static $logPath;

    public function setUp()
    {
        parent::setUp();

        $this->skipIfTestServerIsNotRunning();

        static::$logPath = __DIR__.'/temp/crawledUrls.txt';

        file_put_contents(static::$logPath, 'start log'.PHP_EOL);
    }

    /** @test */
    public function it_will_crawl_all_found_urls()
    {
        Crawler::create()
            ->setCrawlObserver(new CrawlLogger())
            ->startCrawling('http://localhost:8080');

        $this->assertCrawledOnce([
            ['url' => 'http://localhost:8080/'],
            ['url' => 'http://localhost:8080/link1', 'foundOn' => 'http://localhost:8080/'],
            ['url' => 'http://localhost:8080/link2', 'foundOn' => 'http://localhost:8080/'],
            ['url' => 'http://localhost:8080/link3', 'foundOn' => 'http://localhost:8080/link2'],
            ['url' => 'http://localhost:8080/notExists', 'foundOn' => 'http://localhost:8080/link3'],
        ]);
    }

    /** @test */
    public function it_uses_a_crawl_profile_to_determine_what_should_be_crawled()
    {
        $crawlProfile = new class implements CrawlProfile {
            public function shouldCrawl(Url $url): bool
            {
                return $url->path !== '/link3';
            }
        };

        Crawler::create()
            ->setCrawlObserver(new CrawlLogger())
            ->setCrawlProfile(new $crawlProfile)
            ->startCrawling('http://localhost:8080');

        $this->assertCrawledOnce([
            ['url' => 'http://localhost:8080/'],
            ['url' => 'http://localhost:8080/link1', 'foundOn' => 'http://localhost:8080/'],
            ['url' => 'http://localhost:8080/link2', 'foundOn' => 'http://localhost:8080/'],
        ]);

        $this->assertNotCrawled([
            ['url' => 'http://localhost:8080/link3'],
        ]);
    }

    protected function assertCrawledOnce($urls)
    {
        $logContent = file_get_contents(static::$logPath);

        foreach ($urls as $url) {
            $logMessage = "hasBeenCrawled: {$url['url']}";

            if (isset($url['foundOn'])) {
                $logMessage .= " - found on {$url['foundOn']}";
            }

            $logMessage .= PHP_EOL;
        }

        $this->assertEquals(1, substr_count($logContent, $logMessage), "Did not find {$logMessage} exactly one time in the log");
    }

    protected function assertNotCrawled($urls)
    {
        $logContent = file_get_contents(static::$logPath);

        foreach ($urls as $url) {
            $logMessage = "hasBeenCrawled: {$url['url']}";

            if (isset($url['foundOn'])) {
                $logMessage .= " - found on {$url['foundOn']}";
            }

            $logMessage .= PHP_EOL;
        }

        $this->assertEquals(0, substr_count($logContent, $logMessage), "Did  find {$logMessage} in the log");
    }

    public static function log(string $text)
    {
        file_put_contents(static::$logPath, $text.PHP_EOL, FILE_APPEND);
    }
}
