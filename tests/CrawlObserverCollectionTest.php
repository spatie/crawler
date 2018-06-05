<?php

namespace Spatie\Crawler\Test;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Request;
use Spatie\Crawler\CrawlUrl;
use GuzzleHttp\Psr7\Response;
use Spatie\Crawler\CrawlObserver;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlObserverCollection;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

class CrawlObserverCollectionTest extends TestCase
{
    /** @var \Spatie\Crawler\CrawlObserver */
    protected $crawlObserver;

    protected function setUp()
    {
        parent::setUp();

        $this->crawlObserver = new class extends CrawlObserver {
            public $crawled = false;

            public $failed = false;

            public function crawled(
                UriInterface $url,
                ResponseInterface $response,
                ?UriInterface $foundOnUrl = null
            ) {
                $this->crawled = true;
            }

            public function crawlFailed(
                UriInterface $url,
                RequestException $requestException,
                ?UriInterface $foundOnUrl = null
            ) {
                $this->failed = true;
            }
        };
    }

    /** @test */
    public function it_can_be_fulfilled()
    {
        $observers = new CrawlObserverCollection([
            $this->crawlObserver,
        ]);

        $observers->crawled(
            CrawlUrl::create(new Uri('')),
            new Response()
        );

        $this->assertTrue($this->crawlObserver->crawled);
        $this->assertFalse($this->crawlObserver->failed);
    }

    /** @test */
    public function it_can_fail()
    {
        $observers = new CrawlObserverCollection([
            $this->crawlObserver,
        ]);

        $uri = new Uri('');

        $observers->crawlFailed(
            CrawlUrl::create($uri),
            new RequestException('', new Request('GET', $uri))
        );

        $this->assertFalse($this->crawlObserver->crawled);
        $this->assertTrue($this->crawlObserver->failed);
    }
}
