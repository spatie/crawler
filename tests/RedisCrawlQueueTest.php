<?php

namespace Spatie\Crawler\Test;

use GuzzleHttp\Psr7\Uri;
use Predis\Client;
use Spatie\Crawler\CrawlQueue\RedisCrawlQueue;
use Spatie\Crawler\CrawlUrl;

class RedisCrawlQueueTest extends TestCase
{
    /** @var \Spatie\Crawler\CrawlQueue\RedisCrawlQueue */
    protected $crawlQueue;
    /** @var \Predis\Client */
    protected $client;
    /**
     * Maximum number of Redis DBs out of the box
     * see http://download.redis.io/redis-stable/redis.conf
     * @var integer
     */
    const MAX_REDIS_DB_OOTB = 16;

    public function setUp()
    {
        parent::setUp();

        // try not to interfere for other applications
        for ($dbNr = 0; $dbNr < self::MAX_REDIS_DB_OOTB; $dbNr++) {
            $this->client = new Client(['database' => $dbNr]);

            // try to find an empty DB
            if ($this->client->dbsize() === 0) {
                break;
            }
        }

        $this->crawlQueue = new RedisCrawlQueue($this->client);
    }

    /** @test */
    public function an_url_can_be_added()
    {
        $status = $this->client->flushdb();

        $crawlUrl = $this->createCrawlUrl('https://example.com');
        $this->crawlQueue->add($crawlUrl);

        $this->assertEquals($crawlUrl, $this->crawlQueue->getFirstPendingUrl());
    }

    /** @test */
    public function it_can_determine_if_there_are_pending_urls()
    {
        $this->client->flushdb();

        $this->assertFalse($this->crawlQueue->hasPendingUrls());

        $this->crawlQueue->add($this->createCrawlUrl('https://example.com'));

        $this->assertTrue($this->crawlQueue->hasPendingUrls());
    }

    /** @test */
    public function it_can_get_an_url_at_the_specified_index()
    {
        $this->client->flushdb();

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
        $this->client->flushdb();

        $crawlUrl = $this->createCrawlUrl('https://example1.com/');

        $this->assertFalse($this->crawlQueue->has($crawlUrl));

        $this->crawlQueue->add($crawlUrl);

        $this->assertTrue($this->crawlQueue->has($crawlUrl));
    }

    /** @test */
    public function it_can_mark_an_url_as_processed()
    {
        $this->client->flushdb();

        $crawlUrl = $this->createCrawlUrl('https://example1.com/');

        $this->assertFalse($this->crawlQueue->hasAlreadyBeenProcessed($crawlUrl));

        $this->crawlQueue->add($crawlUrl);

        $this->assertFalse($this->crawlQueue->hasAlreadyBeenProcessed($crawlUrl));

        $this->crawlQueue->markAsProcessed($crawlUrl);

        $this->assertTrue($this->crawlQueue->hasAlreadyBeenProcessed($crawlUrl));
    }

    /** @test */
    public function it_can_remove_all_processed_urls_from_the_pending_urls()
    {
        $this->client->flushdb();
        
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
