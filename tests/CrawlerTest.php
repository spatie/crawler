<?php

namespace Spatie\Crawler\Test;

use GuzzleHttp\Psr7\Uri;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlProfile;
use Psr\Http\Message\UriInterface;
use Spatie\Browsershot\Browsershot;
use Spatie\Crawler\CrawlSubdomains;
use Spatie\Crawler\CrawlInternalUrls;
use Spatie\Crawler\EmptyCrawlObserver;

class CrawlerTest extends TestCase
{
    /** @var string logPath */
    protected static $logPath;

    public function setUp()
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

    /** @test */
    public function it_will_not_crawl_tel_links()
    {
        Crawler::create()
            ->setCrawlObserver(new CrawlLogger())
            ->startCrawling('http://localhost:8080');

        $this->assertNotCrawled([
            ['url' => 'http://localhost:8080/tel:123', 'foundOn' => 'http://localhost:8080/']
        ]);
    }

    /** @test */
    public function it_will_handle_multiple_observers()
    {
        Crawler::create()
            ->addCrawlObserver(new CrawlLogger('Observer A'))
            ->addCrawlObserver(new CrawlLogger('Observer B'))
            ->startCrawling('http://localhost:8080');

        $this->assertContains('Observer A', $this->getLogContents());
        $this->assertContains('Observer B', $this->getLogContents());
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

        $this->assertContains('Observer A', $this->getLogContents());
        $this->assertContains('Observer B', $this->getLogContents());
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
        $crawlProfile = new class implements CrawlProfile
        {
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
        $crawlProfile = new class implements CrawlProfile
        {
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
                ->setMaximumCrawlCount($maximumCrawlCount)
                ->setCrawlObserver(new CrawlLogger())
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
        file_put_contents(static::$logPath, $text . PHP_EOL, FILE_APPEND);
    }

    /** @test */
    public function the_empty_crawl_observer_does_nothing()
    {
        Crawler::create()
            ->setCrawlObserver(new EmptyCrawlObserver())
            ->startCrawling('http://localhost:8080');

        $this->assertTrue(true);
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

    protected function regularUrls(): array
    {
        return [
            ['url' => 'http://localhost:8080/'],
            ['url' => 'http://localhost:8080/link1', 'foundOn' => 'http://localhost:8080/'],
            ['url' => 'http://localhost:8080/link2', 'foundOn' => 'http://localhost:8080/'],
            ['url' => 'http://localhost:8080/link3', 'foundOn' => 'http://localhost:8080/link2'],
            ['url' => 'http://localhost:8080/notExists', 'foundOn' => 'http://localhost:8080/link3'],
            ['url' => 'http://example.com/', 'foundOn' => 'http://localhost:8080/link1'],
            ['url' => 'http://localhost:8080/dir/link4', 'foundOn' => 'http://localhost:8080/'],
            ['url' => 'http://localhost:8080/dir/link5', 'foundOn' => 'http://localhost:8080/dir/link4'],
            ['url' => 'http://localhost:8080/dir/subdir/link6', 'foundOn' => 'http://localhost:8080/dir/link5'],
        ];
    }

    protected function javascriptInjectedUrls(): array
    {
        return [
            ['url' => 'http://localhost:8080/javascript', 'foundOn' => 'http://localhost:8080/link1'],
        ];
    }

    public function getLogContents(): string
    {
        return file_get_contents(static::$logPath);
    }

    protected function assertCrawledOnce($urls)
    {
        $logContent = $this->getLogContents();

        foreach ($urls as $url) {
            $logMessage = "hasBeenCrawled: {$url['url']}";

            if (isset($url['foundOn'])) {
                $logMessage .= " - found on {$url['foundOn']}";
            }

            $logMessage .= PHP_EOL;

            $this->assertEquals(1, substr_count($logContent, $logMessage), "Did not find {$logMessage} exactly one time in the log but " . substr_count($logContent, $logMessage) . " times. Contents of log\n{$logContent}");
        }
    }

    protected function assertNotCrawled($urls)
    {
        $logContent = $this->getLogContents();

        foreach ($urls as $url) {
            $logMessage = "hasBeenCrawled: {$url['url']}";

            if (isset($url['foundOn'])) {
                $logMessage .= " - found on {$url['foundOn']}";
            }

            $logMessage .= PHP_EOL;

            $this->assertEquals(0, substr_count($logContent, $logMessage), "Did find {$logMessage} in the log");
        }
    }

    protected function assertCrawledUrlCount(int $count)
    {
        $logContent = file_get_contents(static::$logPath);

        $actualCount = substr_count($logContent, 'hasBeenCrawled');

        $this->assertEquals($count, $actualCount, "Crawled `{$actualCount}` urls instead of the expected {$count}");
    }

    public function resetLog()
    {
        static::$logPath = __DIR__ . '/temp/crawledUrls.txt';

        file_put_contents(static::$logPath, 'start log' . PHP_EOL);
    }
}
