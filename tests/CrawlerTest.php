<?php

namespace Spatie\Crawler\Test;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\UriInterface;
use Spatie\Browsershot\Browsershot;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlProfiles\CrawlInternalUrls;
use Spatie\Crawler\CrawlProfiles\CrawlProfile;
use Spatie\Crawler\CrawlProfiles\CrawlSubdomains;
use Spatie\Crawler\Exceptions\InvalidCrawlRequestHandler;
use Spatie\Crawler\Test\TestClasses\CrawlLogger;
use stdClass;

class CrawlerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->skipIfTestServerIsNotRunning();

        $this->resetLog();
    }

    /** @test */
    public function it_will_crawl_all_found_urls()
    {
        Crawler::create()
            ->setCrawlObserver(new CrawlLogger())
            ->startCrawling('http://localhost:8080');

        $this->assertCrawledOnce($this->regularUrls());

        $this->assertNotCrawled($this->javascriptInjectedUrls());
    }

    protected function javascriptInjectedUrls(): array
    {
        return [
            ['url' => 'http://localhost:8080/javascript', 'foundOn' => 'http://localhost:8080/link1'],
        ];
    }

    /** @test */
    public function it_will_not_crawl_tel_links()
    {
        Crawler::create()
            ->setCrawlObserver(new CrawlLogger())
            ->startCrawling('http://localhost:8080');

        $this->assertNotCrawled([
            ['url' => 'http://localhost:8080/tel:123', 'foundOn' => 'http://localhost:8080/'],
        ]);
    }

    /** @test */
    public function it_will_handle_multiple_observers()
    {
        Crawler::create()
            ->addCrawlObserver(new CrawlLogger('Observer A'))
            ->addCrawlObserver(new CrawlLogger('Observer B'))
            ->startCrawling('http://localhost:8080');

        $this->assertStringContainsString('Observer A', $this->getLogContents());
        $this->assertStringContainsString('Observer B', $this->getLogContents());
    }

    /** @test */
    public function multiple_observers_can_be_set_at_once()
    {
        Crawler::create()
            ->setCrawlObservers([
                new CrawlLogger('Observer A'),
                new CrawlLogger('Observer B'),
            ])
            ->startCrawling('http://localhost:8080');

        $this->assertStringContainsString('Observer A', $this->getLogContents());
        $this->assertStringContainsString('Observer B', $this->getLogContents());
    }

    /** @test */
    public function it_can_crawl_uris_without_scheme()
    {
        Crawler::create()
            ->setCrawlObserver(new CrawlLogger())
            ->startCrawling('localhost:8080');

        $this->assertCrawledOnce($this->regularUrls());
    }

    /** @test */
    public function it_can_crawl_all_links_rendered_by_javascript()
    {
        $crawler = Crawler::create();

        if (getenv('TRAVIS')) {
            $browsershot = new Browsershot();

            $browsershot->noSandbox();

            $crawler->setBrowsershot($browsershot);
        }

        $crawler
            ->executeJavaScript()
            ->setCrawlObserver(new CrawlLogger())
            ->startCrawling('http://localhost:8080');

        $this->assertCrawledOnce($this->regularUrls());

        $this->assertCrawledOnce($this->javascriptInjectedUrls());
    }

    /** @test */
    public function it_allows_for_a_browsershot_instance_to_be_set()
    {
        $browsershot = new Browsershot();

        if (getenv('TRAVIS')) {
            $browsershot->noSandbox();
        }

        Crawler::create()
            ->setBrowsershot($browsershot)
            ->executeJavaScript()
            ->setCrawlObserver(new CrawlLogger())
            ->startCrawling('http://localhost:8080');

        $this->assertCrawledOnce($this->regularUrls());

        $this->assertCrawledOnce($this->javascriptInjectedUrls());
    }

    /** @test */
    public function it_has_a_method_to_disable_executing_javascript()
    {
        Crawler::create()
            ->executeJavaScript()
            ->doNotExecuteJavaScript()
            ->setCrawlObserver(new CrawlLogger())
            ->startCrawling('http://localhost:8080');

        $this->assertCrawledOnce($this->regularUrls());

        $this->assertNotCrawled($this->javascriptInjectedUrls());
    }

    /** @test */
    public function it_uses_a_crawl_profile_to_determine_what_should_be_crawled()
    {
        $crawlProfile = new class extends CrawlProfile {
            public function shouldCrawl(UriInterface $url): bool
            {
                return $url->getPath() !== '/link3';
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

    /** @test */
    public function it_uses_crawl_profile_for_internal_urls()
    {
        Crawler::create()
            ->setCrawlObserver(new CrawlLogger())
            ->setCrawlProfile(new CrawlInternalUrls('localhost:8080'))
            ->startCrawling('http://localhost:8080');

        $this->assertCrawledOnce([
            ['url' => 'http://localhost:8080/link1', 'foundOn' => 'http://localhost:8080/'],
        ]);

        $this->assertNotCrawled([
            ['url' => 'http://example.com/'],
        ]);
    }

    /** @test */
    public function it_can_handle_pages_with_invalid_urls()
    {
        $crawlProfile = new class extends CrawlProfile {
            public function shouldCrawl(UriInterface $url): bool
            {
                return true;
            }
        };

        Crawler::create()
            ->setCrawlObserver(new CrawlLogger())
            ->setCrawlProfile($crawlProfile)
            ->startCrawling('localhost:8080/invalid-url');

        $this->assertCrawledOnce([
            ['url' => 'http://localhost:8080/invalid-url'],
        ]);
    }

    /** @test */
    public function it_respects_the_maximum_amount_of_urls_to_be_crawled()
    {
        foreach (range(1, 8) as $maximumCrawlCount) {
            $this->resetLog();

            Crawler::create()
                ->setTotalCrawlLimit($maximumCrawlCount)
                ->setCrawlObserver(new CrawlLogger())
                ->ignoreRobots()
                ->setCrawlProfile(new CrawlInternalUrls('localhost:8080'))
                ->startCrawling('http://localhost:8080');

            $this->assertCrawledUrlCount($maximumCrawlCount);
        }
    }

    /** @test */
    public function it_doesnt_extract_links_if_the_crawled_page_exceeds_the_maximum_response_size()
    {
        Crawler::create()
            ->setCrawlObserver(new CrawlLogger())
            ->setMaximumResponseSize(10)
            ->startCrawling('http://localhost:8080');

        $this->assertCrawledOnce([
            ['url' => 'http://localhost:8080/'],
        ]);

        $this->assertNotCrawled([
            ['url' => 'http://localhost:8080/link1', 'foundOn' => 'http://localhost:8080/'],
            ['url' => 'http://localhost:8080/link2', 'foundOn' => 'http://localhost:8080/'],
            ['url' => 'http://localhost:8080/dir/link4', 'foundOn' => 'http://localhost:8080/'],
        ]);
    }

    /** @test */
    public function it_will_crawl_to_specified_depth()
    {
        Crawler::create()
            ->setCrawlObserver(new CrawlLogger())
            ->setMaximumDepth(1)
            ->startCrawling('http://localhost:8080');

        $this->assertCrawledOnce([
            ['url' => 'http://localhost:8080/'],
            ['url' => 'http://localhost:8080/link1', 'foundOn' => 'http://localhost:8080/'],
            ['url' => 'http://localhost:8080/link2', 'foundOn' => 'http://localhost:8080/'],
            ['url' => 'http://localhost:8080/dir/link4', 'foundOn' => 'http://localhost:8080/'],
        ]);

        $this->assertNotCrawled([
            ['url' => 'http://example.com/'],
            ['url' => 'http://localhost:8080/link3'],
            ['url' => 'http://localhost:8080/notExists'],
            ['url' => 'http://localhost:8080/dir/link5'],
            ['url' => 'http://localhost:8080/dir/subdir/link5'],
        ]);

        $this->resetLog();

        Crawler::create()
            ->setCrawlObserver(new CrawlLogger())
            ->setMaximumDepth(2)
            ->startCrawling('http://localhost:8080');

        $this->assertCrawledOnce([
            ['url' => 'http://localhost:8080/'],
            ['url' => 'http://localhost:8080/link1', 'foundOn' => 'http://localhost:8080/'],
            ['url' => 'http://localhost:8080/link2', 'foundOn' => 'http://localhost:8080/'],
            ['url' => 'http://localhost:8080/dir/link4', 'foundOn' => 'http://localhost:8080/'],
            ['url' => 'http://example.com/', 'foundOn' => 'http://localhost:8080/link1'],
            ['url' => 'http://localhost:8080/link3', 'foundOn' => 'http://localhost:8080/link2'],
            ['url' => 'http://localhost:8080/dir/link5', 'foundOn' => 'http://localhost:8080/dir/link4'],
        ]);

        $this->assertNotCrawled([
            ['url' => 'http://localhost:8080/notExists'],
            ['url' => 'http://localhost:8080/dir/link5'],
            ['url' => 'http://localhost:8080/dir/subdir/link5'],
        ]);
    }

    public static function log(string $text)
    {
        file_put_contents(static::$logPath, $text.PHP_EOL, FILE_APPEND);
    }

    /** @test */
    public function profile_crawls_a_domain_and_its_subdomains()
    {
        $baseUrl = 'http://spatie.be';

        $urls = [
            'http://spatie.be' => true,
            'http://subdomain.spatie.be' => true,
            'https://www.subdomain.spatie.be' => true,
            'https://sub.dom.ain.spatie.be' => true,
            'https://subdomain.localhost:8080' => false,
            'https://localhost:8080' => false,
        ];

        $profile = new CrawlSubdomains($baseUrl);

        foreach ($urls as $url => $bool) {
            $this->assertEquals($bool, $profile->isSubdomainOfHost(new Uri($url)));
        }
    }

    /** @test */
    public function it_crawls_subdomains()
    {
        $baseUrl = 'http://localhost:8080';

        Crawler::create()
            ->setCrawlObserver(new CrawlLogger())
            ->setMaximumDepth(2)
            ->setCrawlProfile(new CrawlSubdomains($baseUrl))
            ->startCrawling($baseUrl);

        $this->assertCrawledOnce([
            ['url' => 'http://localhost:8080/'],
            ['url' => 'http://localhost:8080/link1', 'foundOn' => 'http://localhost:8080/'],
            ['url' => 'http://localhost:8080/link2', 'foundOn' => 'http://localhost:8080/'],
            ['url' => 'http://localhost:8080/dir/link4', 'foundOn' => 'http://localhost:8080/'],
            ['url' => 'http://localhost:8080/link3', 'foundOn' => 'http://localhost:8080/link2'],
            ['url' => 'http://localhost:8080/dir/link5', 'foundOn' => 'http://localhost:8080/dir/link4'],
            ['url' => 'http://sub.localhost:8080/subdomainpage', 'foundOn' => 'http://localhost:8080/link2'],
            ['url' => 'http://subdomain.sub.localhost:8080/subdomainpage', 'foundOn' => 'http://localhost:8080/link2'],
        ]);

        $this->assertNotCrawled([
            ['url' => 'http://localhost:8080/notExists'],
            ['url' => 'http://localhost:8080/dir/link5'],
            ['url' => 'http://localhost:8080/dir/subdir/link5'],
            ['url' => 'http://example.com/', 'foundOn' => 'http://localhost:8080/link1'],
        ]);
    }

    /** @test */
    public function it_should_not_follow_nofollow_links()
    {
        Crawler::create()
            ->setCrawlObserver(new CrawlLogger())
            ->setMaximumDepth(1)
            ->startCrawling('http://localhost:8080');

        $this->assertNotCrawled([['url' => 'http://localhost:8080/nofollow', 'foundOn' => 'http://localhost:8080/']]);
    }

    /** @test */
    public function it_should_handle_redirects_correctly_when_tracking_is_active()
    {
        Crawler::create([
            RequestOptions::ALLOW_REDIRECTS => [
                'track_redirects' => true,
            ],
        ])
            ->setCrawlObserver(new CrawlLogger())
            ->startCrawling('http://localhost:8080/dir1/internal-redirect-entry/');

        $this->assertCrawledUrlCount(3);
    }

    protected function regularUrls(): array
    {
        return [
            ['url' => 'http://localhost:8080/'],
            ['url' => 'http://localhost:8080/link1', 'foundOn' => 'http://localhost:8080/'],
            ['url' => 'http://localhost:8080/link1-prev', 'foundOn' => 'http://localhost:8080/link1'],
            ['url' => 'http://localhost:8080/link1-next', 'foundOn' => 'http://localhost:8080/link1'],
            ['url' => 'http://localhost:8080/link2', 'foundOn' => 'http://localhost:8080/'],
            ['url' => 'http://localhost:8080/link3', 'foundOn' => 'http://localhost:8080/link2'],
            ['url' => 'http://localhost:8080/notExists', 'foundOn' => 'http://localhost:8080/link3'],
            ['url' => 'http://example.com/', 'foundOn' => 'http://localhost:8080/link1'],
            ['url' => 'http://localhost:8080/dir/link4', 'foundOn' => 'http://localhost:8080/'],
            ['url' => 'http://localhost:8080/dir/link5', 'foundOn' => 'http://localhost:8080/dir/link4'],
            ['url' => 'http://localhost:8080/dir/subdir/link6', 'foundOn' => 'http://localhost:8080/dir/link5'],
        ];
    }

    /** @test */
    public function it_respects_the_requested_delay_between_requests()
    {
        $baseUrl = 'http://localhost:8080';

        $start = time();

        Crawler::create()
            ->setCrawlObserver(new CrawlLogger())
            ->setMaximumDepth(2)
            ->setDelayBetweenRequests(500) // 500ms
            ->setCrawlProfile(new CrawlSubdomains($baseUrl))
            ->startCrawling($baseUrl);

        $end = time();

        $diff = $end - $start;

        // At 500ms delay per URL, crawling 8 URLs should take at least 4 seconds.
        $this->assertGreaterThan(4, $diff);
    }

    /** @test */
    public function custom_crawl_request_handlers_must_extend_abstracts()
    {
        $this->expectException(InvalidCrawlRequestHandler::class);

        Crawler::create()->setCrawlFulfilledHandlerClass(stdClass::class);

        $this->expectException(InvalidCrawlRequestHandler::class);

        Crawler::create()->setCrawlFailedHandlerClass(stdClass::class);
    }

    /** @test */
    public function it_should_ignore_user_agents_header_case()
    {
        $clientConfig = ['headers' => ['user-agent' => 'foo']];
        $newUserAgent = 'bar';

        $crawler = Crawler::create($clientConfig)->setUserAgent($newUserAgent);
        $actualUserAgent = $crawler->getUserAgent();

        $this->assertSame($newUserAgent, $actualUserAgent);
    }

    /** @test */
    public function it_will_only_crawl_correct_mime_types_when_asked_to()
    {
        Crawler::create()
            ->setCrawlObserver(new CrawlLogger())
            ->setParseableMimeTypes(['text/html', 'text/plain'])
            ->startCrawling('http://localhost:8080/content-types');

        $this->assertNotCrawled([['url' => 'http://localhost:8080/content-types/music.html', 'foundOn' => 'http://localhost:8080/content-types/music.mp3']]);
        $this->assertNotCrawled([['url' => 'http://localhost:8080/content-types/video.html', 'foundOn' => 'http://localhost:8080/content-types/video.mkv']]);

        $this->assertCrawledOnce([['url' => 'http://localhost:8080/content-types/normal.html', 'foundOn' => 'http://localhost:8080/content-types']]);

        $this->assertCrawledUrlCount(4);
    }

    /** @test */
    public function it_will_crawl_all_content_types_when_not_explicitly_whitelisted()
    {
        Crawler::create()
            ->setCrawlObserver(new CrawlLogger())
            ->startCrawling('http://localhost:8080/content-types');

        $this->assertCrawledOnce([['url' => 'http://localhost:8080/content-types/music.html', 'foundOn' => 'http://localhost:8080/content-types/music.mp3']]);
        $this->assertCrawledOnce([['url' => 'http://localhost:8080/content-types/video.html', 'foundOn' => 'http://localhost:8080/content-types/video.mkv']]);

        $this->assertCrawledUrlCount(6);
    }

    /** @test */
    public function it_will_allow_streaming_responses_when_the_client_asks_for_it()
    {
        $clientConfig = ['stream' => true];

        Crawler::create($clientConfig)
            ->setCrawlObserver(new CrawlLogger())
            ->startCrawling('http://localhost:8080/content-types');

        $this->assertCrawledOnce([['url' => 'http://localhost:8080/content-types/music.html', 'foundOn' => 'http://localhost:8080/content-types/music.mp3']]);
        $this->assertCrawledOnce([['url' => 'http://localhost:8080/content-types/video.html', 'foundOn' => 'http://localhost:8080/content-types/video.mkv']]);

        $this->assertCrawledUrlCount(6);
    }

    /** @test */
    public function it_will_not_crawl_half_parsed_href_tags()
    {
        Crawler::create()
            ->setCrawlObserver(new CrawlLogger())
            ->startCrawling('http://localhost:8080/incomplete-href');

        $this->assertNotCrawled([['url' => 'http://localhost:8080/invalid-link', 'foundOn' => 'http://localhost:8080/incomplete-href']]);

        $this->assertCrawledUrlCount(3);
    }
}
