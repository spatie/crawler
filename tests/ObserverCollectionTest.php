<?php

namespace Spatie\Crawler\Test;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlObserver;
use Spatie\Crawler\CrawlUrl;
use Spatie\Crawler\ObserverCollection;

class ObserverCollectionTest extends TestCase
{
    private $observer;

    public static $crawled = false;

    public static $failed = false;

    protected function setUp()
    {
        parent::setUp();

        $this->observer = new class extends CrawlObserver
        {
            public function crawled(
                UriInterface $url,
                ResponseInterface $response,
                ?UriInterface $foundOnUrl = null
            ) {
                ObserverCollectionTest::$crawled = true;
            }

            public function crawlFailed(
                UriInterface $url,
                RequestException $requestException,
                ?UriInterface $foundOnUrl = null
            ) {
                ObserverCollectionTest::$failed = true;
            }
        };

        self::$crawled = false;
        self::$failed = false;
    }

    /** @test */
    public function it_can_be_fulfill()
    {
        $observers = new ObserverCollection([
            $this->observer,
        ]);

        $observers->crawled(CrawlUrl::create(new Uri('')), new Response());

        $this->assertTrue(self::$crawled);

        $this->assertFalse(self::$failed);
    }

    /** @test */
    public function it_can_fail()
    {
        $observers = new ObserverCollection([
            $this->observer,
        ]);

        $uri = new Uri('');

        $observers->crawlFailed(CrawlUrl::create($uri), new RequestException('', new Request('GET', $uri)));

        $this->assertFalse(self::$crawled);

        $this->assertTrue(self::$failed);
    }
}
