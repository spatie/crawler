<?php

namespace Spatie\Crawler\Test;

use Spatie\Crawler\Crawler;
use Spatie\Crawler\Test\TestClasses\CrawlLogger;

class CrawlerRobotsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->skipIfTestServerIsNotRunning();

        $this->resetLog();
    }

    /**
     * @return Crawler
     */
    private function createCrawler(): Crawler
    {
        return Crawler::create()
            ->setMaximumDepth(3)
            ->setCrawlObserver(new CrawlLogger());
    }

    /** @test */
    public function it_should_not_follow_robots_txt_disallowed_links()
    {
        $this->createCrawler()
            ->startCrawling('http://localhost:8080');

        $this->assertNotCrawled([['url' => 'http://localhost:8080/txt-disallow', 'foundOn' => 'http://localhost:8080/']]);
    }

    /** @test */
    public function it_does_not_allow_a_root_ignored_url()
    {
        $this->createCrawler()
            ->startCrawling('http://localhost:8080/txt-disallow');

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
    public function it_should_follow_robots_meta_follow_links()
    {
        $this->createCrawler()
            ->startCrawling('http://localhost:8080');

        $this->assertCrawledOnce([['url' => 'http://localhost:8080/meta-nofollow', 'foundOn' => 'http://localhost:8080/meta-follow']]);
    }

    /** @test */
    public function it_should_follow_robots_meta_nofollow_links_when_robots_are_ignored()
    {
        $this->createCrawler()
            ->ignoreRobots()
            ->startCrawling('http://localhost:8080');

        $this->assertCrawledOnce([['url' => 'http://localhost:8080/meta-nofollow-target', 'foundOn' => 'http://localhost:8080/meta-nofollow']]);
    }

    /** @test */
    public function it_should_not_index_robots_meta_noindex()
    {
        $this->createCrawler()
            ->startCrawling('http://localhost:8080');

        $this->assertCrawledOnce([['url' => 'http://localhost:8080/meta-nofollow', 'foundOn' => 'http://localhost:8080/meta-follow']]);

        $this->assertNotCrawled([
            ['url' => 'http://localhost:8080/meta-follow'],
        ]);
    }

    /** @test */
    public function it_should_index_robots_meta_noindex_when_robots_are_ignored()
    {
        $this->createCrawler()
            ->ignoreRobots()
            ->startCrawling('http://localhost:8080');

        $this->assertCrawledOnce([
            ['url' => 'http://localhost:8080/meta-follow', 'foundOn' => 'http://localhost:8080/'],
        ]);
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

    /** @test */
    public function it_should_check_depth_when_respecting_robots()
    {
        Crawler::create()
            ->respectRobots()
            ->setMaximumDepth(1)
            ->setCrawlObserver(new CrawlLogger())
            ->startCrawling('http://localhost:8080');

        $this->assertNotCrawled([['url' => 'http://localhost:8080/link3', 'foundOn' => 'http://localhost:8080/link2']]);
    }

    /** @test */
    public function it_should_return_the_already_set_user_agent()
    {
        $crawler = Crawler::create()
            ->setUserAgent('test/1.2.3');

        $this->assertEquals('test/1.2.3', $crawler->getUserAgent());
    }

    /** @test */
    public function it_should_return_the_user_agent_set_by_constructor()
    {
        $crawler = Crawler::create(['headers' => ['User-Agent' => 'test/1.2.3']]);

        $this->assertEquals('test/1.2.3', $crawler->getUserAgent());
    }

    /** @test */
    public function it_should_return_the_last_set_user_agent()
    {
        $crawler = Crawler::create(['headers' => ['User-Agent' => 'test/1.2.3']])
            ->setUserAgent('test/4.5.6');

        $this->assertEquals('test/4.5.6', $crawler->getUserAgent());
    }

    /** @test */
    public function it_should_return_default_user_agent_when_none_is_set()
    {
        $crawler = Crawler::create();

        $this->assertNotEmpty($crawler->getUserAgent());
    }

    /** @test */
    public function it_should_remember_settings()
    {
        $crawler = Crawler::create()
            ->setMaximumDepth(10)
            ->setMaximumCrawlCount(10)
            ->setUserAgent('test/1.2.3');

        $this->assertEquals(10, $crawler->getMaximumDepth());
        $this->assertEquals(10, $crawler->getMaximumCrawlCount());
        $this->assertEquals('test/1.2.3', $crawler->getUserAgent());
    }

    /** @test */
    public function it_should_check_depth_when_ignoring_robots()
    {
        Crawler::create()
            ->ignoreRobots()
            ->setMaximumDepth(1)
            ->setCrawlObserver(new CrawlLogger())
            ->startCrawling('http://localhost:8080');

        $this->assertNotCrawled([['url' => 'http://localhost:8080/link3', 'foundOn' => 'http://localhost:8080/link2']]);
    }

    /** @test */
    public function it_should_respect_custom_user_agent_rules()
    {
        // According to Robots docs only
        // one group out of the robots.txt file applies.
        // So wildcard (*) instructions should be ignored
        // by the more specific agent instructions
        // @see https://developers.google.com/search/reference/robots_txt
        // @see https://en.wikipedia.org/wiki/Robots_exclusion_standard

        Crawler::create()
            ->respectRobots()
            ->setMaximumDepth(1)
            ->setCrawlObserver(new CrawlLogger())
            ->setUserAgent('my-agent')
            ->startCrawling('http://localhost:8080');

        $this->assertNotCrawled([['url' => 'http://localhost:8080/txt-disallow-custom-user-agent', 'foundOn' => 'http://localhost:8080/']]);
        $this->assertNotCrawled([['url' => 'http://localhost:8080/txt-disallow', 'foundOn' => 'http://localhost:8080/']]);
        $this->assertCrawledOnce([['url' => 'http://localhost:8080/link1', 'foundOn' => 'http://localhost:8080/']]);
    }
}
