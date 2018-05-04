<?php

namespace Spatie\Crawler\Test;

use Spatie\Crawler\Crawler;

class CrawlerRobotsTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->skipIfTestServerIsNotRunning();

        $this->resetLog();
    }

    /** @test */
    public function it_should_not_follow_robots_txt_disallowed_links()
    {
        $this->createCrawler()
            ->startCrawling('http://localhost:8080');

        $this->assertNotCrawled([['url' => 'http://localhost:8080/txt-disallow', 'foundOn' => 'http://localhost:8080/']]);
    }

    /** @test */
    public function it_should_follow_robots_txt_disallowed_links_when_robots_are_ignored()
    {
        $this->createCrawler()
            ->ignoreRobots()
            ->startCrawling('http://localhost:8080');

        $this->assertCrawledOnce([['url' => 'http://localhost:8080/txt-disallow', 'foundOn' => 'http://localhost:8080/']]);
    }

    /** @test */
    public function it_should_not_follow_robots_meta_disallowed_links()
    {
        $this->createCrawler()
            ->startCrawling('http://localhost:8080');

        $this->assertNotCrawled([['url' => 'http://localhost:8080/meta-disallow', 'foundOn' => 'http://localhost:8080/']]);
    }

    /** @test */
    public function it_should_follow_robots_meta_disallowed_links_when_robots_are_ignored()
    {
        $this->createCrawler()
            ->ignoreRobots()
            ->startCrawling('http://localhost:8080');

        $this->assertCrawledOnce([['url' => 'http://localhost:8080/meta-disallow', 'foundOn' => 'http://localhost:8080/']]);
    }

    /** @test */
    public function it_should_not_follow_robots_header_disallowed_links()
    {
        $this->createCrawler()
            ->startCrawling('http://localhost:8080');

        $this->assertNotCrawled([['url' => 'http://localhost:8080/header-disallow', 'foundOn' => 'http://localhost:8080/']]);
    }

    /** @test */
    public function it_should_follow_robots_header_disallowed_links_when_robots_are_ignored()
    {
        $this->createCrawler()
            ->ignoreRobots()
            ->startCrawling('http://localhost:8080');

        $this->assertCrawledOnce([['url' => 'http://localhost:8080/header-disallow', 'foundOn' => 'http://localhost:8080/']]);
    }

    private function createCrawler(): Crawler
    {
        return Crawler::create()
            ->setMaximumDepth(1)
            ->setCrawlObserver(new CrawlLogger());
    }
}
