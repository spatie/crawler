<?php

namespace Spatie\Crawler\Test;

use GuzzleHttp\Psr7\Uri;
use Spatie\Crawler\CrawlUrl;
use Spatie\Crawler\CrawlQueue\ArrayCrawlQueue;

class ArrayCrawlQueueTest extends TestCase
{
    /** @var \Spatie\Crawler\CrawlQueue\CollectionCrawlQueue */
    protected $crawlQueue;

    public function setUp()
    {
        parent::setUp();

        $this->crawlQueue = new ArrayCrawlQueue();
    }

    /** @test */
    public function an_url_can_be_added()
    {
        $crawlUrl = $this->createCrawlUrl('https://example.com');
        $this->crawlQueue->add($crawlUrl);

        $this->assertEquals($crawlUrl, $this->crawlQueue->getFirstPendingUrl());
    }

    /** @test */
    public function it_can_determine_if_there_are_pending_urls()
    {
        $this->assertFalse($this->crawlQueue->hasPendingUrls());

        $this->crawlQueue->add($this->createCrawlUrl('https://example.com'));

        $this->assertTrue($this->crawlQueue->hasPendingUrls());
    }

    /** @test */
    public function it_can_get_an_url_at_the_specified_index()
    {
        $url1 = $this->createCrawlUrl('https://example1.com/');
        $url2 = $this->createCrawlUrl('https://example2.com/');

        $this->crawlQueue->add($url1);
        $this->crawlQueue->add($url2);

        $this->assertEquals(
            'https://example1.com/',
            (string) $this->crawlQueue->getUrlById($url1->getId())->url
        );
        $this->assertEquals(
            'https://example2.com/',
            (string) $this->crawlQueue->getUrlById($url2->getId())->url
        );
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

        $pendingUrlCount = 0;

        while ($url = $this->crawlQueue->getFirstPendingUrl()) {
            $pendingUrlCount++;
            $this->crawlQueue->markAsProcessed($url);
        }

        $this->assertEquals(1, $pendingUrlCount);
    }

    protected function createCrawlUrl(string $url): CrawlUrl
    {
        return CrawlUrl::create(new Uri($url));
    }
}
