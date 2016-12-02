<?php

namespace Spatie\Crawler\Test;

use Spatie\Crawler\CrawlQueue;
use Spatie\Crawler\CrawlUrl;
use Spatie\Crawler\Url;

class CrawlQueueTest extends TestCase
{
    /**
     * @var \Spatie\Crawler\CrawlQueue
     */
    protected $crawlQueue;

    public function setUp()
    {
        parent::setUp();

        $this->crawlQueue = new CrawlQueue();
    }

    /** @test */
    public function an_url_can_be_added()
    {
        $this->crawlQueue->add($this->createCrawlUrl('https://example.com'));

        $this->assertCount(1, $this->crawlQueue->getPendingUrls());
    }

    /** @test */
    public function it_can_determine_if_there_are_pending_urls()
    {
        $this->assertFalse($this->crawlQueue->hasPendingUrls());

        $this->crawlQueue->add($this->createCrawlUrl('https://example.com'));

        $this->assertTrue($this->crawlQueue->hasPendingUrls());
    }

    /** @test */
    public function it_can_get_a_pending_url_at_the_specified_index()
    {
        $this->crawlQueue->add($this->createCrawlUrl('https://example1.com/'));
        $this->crawlQueue->add($this->createCrawlUrl('https://example2.com/'));

        $this->assertEquals('https://example1.com/', (string) $this->crawlQueue->getPendingUrlAtIndex(0)->url);
        $this->assertEquals('https://example2.com/', (string) $this->crawlQueue->getPendingUrlAtIndex(1)->url);
    }

    /** @test */
    public function it_can_determine_if_has_a_given_url()
    {
        $crawlUrl = $this->createCrawlUrl('https://example1.com/');

        $this->assertFalse($this->crawlQueue->has($crawlUrl));

        $this->crawlQueue->add($crawlUrl);

        $this->assertTrue($this->crawlQueue->has($crawlUrl));
    }

    /** @test */
    public function it_can_mark_an_url_as_processed()
    {
        $crawlUrl = $this->createCrawlUrl('https://example1.com/');

        $this->crawlQueue->add($crawlUrl);

        $this->assertFalse($this->crawlQueue->hasAlreadyBeenProcessed($crawlUrl));

        $this->crawlQueue->markAsProcessed($crawlUrl);

        $this->assertTrue($this->crawlQueue->hasAlreadyBeenProcessed($crawlUrl));
    }

    /** @test */
    public function it_can_remove_all_processed_urls_from_the_pending_urls()
    {
        $crawlUrl1 = $this->createCrawlUrl('https://example1.com/');
        $crawlUrl2 = $this->createCrawlUrl('https://example2.com/');

        $this->crawlQueue
            ->add($crawlUrl1)
            ->add($crawlUrl2);

        $this->crawlQueue->markAsProcessed($crawlUrl1);

        $this->crawlQueue->removeProcessedUrlsFromPending();

        $this->assertCount(1, $this->crawlQueue->getPendingUrls());

        $crawlUrl = $this->crawlQueue->getPendingUrlAtIndex(0);

        $this->assertEquals('https://example2.com/', (string) $crawlUrl->url);
    }

    protected function createCrawlUrl(string $url): CrawlUrl
    {
        $url = new Url($url);

        return CrawlUrl::create($url);
    }
}
