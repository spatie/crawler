<?php

namespace Spatie\Crawler\Test;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlObserver;
use Spatie\Crawler\CrawlObserverCollection;
use Spatie\Crawler\CrawlUrl;

class CrawlObserverCollectionTest extends TestCase
{
    /** @var \Spatie\Crawler\CrawlObserver */
    protected $crawlObserver;

    protected function setUp(): void
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
