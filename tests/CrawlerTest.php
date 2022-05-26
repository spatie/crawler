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
use Spatie\Crawler\Test\TestClasses\Log;
use stdClass;

beforeEach(function () {
    skipIfTestServerIsNotRunning();

    Log::reset();
});

it('will crawl all found urls', function () {
    createCrawler()->startCrawling('http://localhost:8080');

    expect(regularUrls())->each->toBeCrawledOnce();

    expect(javascriptInjectedUrls())->each->notToBeCrawled();
});

it('will not crawl tel links', function () {
    createCrawler()->startCrawling('http://localhost:8080');

    expect(['url' => 'http://localhost:8080/tel:123', 'foundOn' => 'http://localhost:8080/'])
        ->notToBeCrawled();
});

it('will handle multiple observers', function () {
    Crawler::create()
        ->addCrawlObserver(new CrawlLogger('Observer A'))
        ->addCrawlObserver(new CrawlLogger('Observer B'))
        ->startCrawling('http://localhost:8080');

    expect(Log::getContents())
        ->toContain('Observer A')
        ->toContain('Observer B');
});

test('multiple observers can be set at once', function () {
    Crawler::create()
        ->setCrawlObservers([
            new CrawlLogger('Observer A'),
            new CrawlLogger('Observer B'),
        ])
        ->startCrawling('http://localhost:8080');

    expect(Log::getContents())
        ->toContain('Observer A')
        ->toContain('Observer B');
});

it('can crawl uris without scheme', function () {
    createCrawler()->startCrawling('localhost:8080');

    expect(regularUrls())->each->toBeCrawledOnce();
});

it('can crawl all links rendered by javascript', function () {
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

    expect(regularUrls())->each->toBeCrawledOnce();

    expect(javascriptInjectedUrls())->each->toBeCrawledOnce();
});

it('allows for a browsershot instance to be set', function () {
    $browsershot = new Browsershot();

    if (getenv('TRAVIS')) {
        $browsershot->noSandbox();
    }

    Crawler::create()
        ->setBrowsershot($browsershot)
        ->executeJavaScript()
        ->setCrawlObserver(new CrawlLogger())
        ->startCrawling('http://localhost:8080');

    expect(regularUrls())->each->toBeCrawledOnce();

    expect(javascriptInjectedUrls())->each->toBeCrawledOnce();
});

it('has a method to disable executing javascript', function () {
    Crawler::create()
        ->executeJavaScript()
        ->doNotExecuteJavaScript()
        ->setCrawlObserver(new CrawlLogger())
        ->startCrawling('http://localhost:8080');

    expect(regularUrls())->each->toBeCrawledOnce();

    expect(javascriptInjectedUrls())->each->notToBeCrawled();
});

it('uses a crawl profile to determine what should be crawled', function () {
    $crawlProfile = new class () extends CrawlProfile {
        public function shouldCrawl(UriInterface $url): bool
        {
            return $url->getPath() !== '/link3';
        }
    };

    createCrawler()
        ->setCrawlProfile(new $crawlProfile())
        ->startCrawling('http://localhost:8080');

    expect([
        ['url' => 'http://localhost:8080/'],
        ['url' => 'http://localhost:8080/link1', 'foundOn' => 'http://localhost:8080/'],
        ['url' => 'http://localhost:8080/link2', 'foundOn' => 'http://localhost:8080/'],
    ])->each->toBeCrawledOnce();

    expect(['url' => 'http://localhost:8080/link3'])->notToBeCrawled();
});

it('uses crawl profile for internal urls', function () {
    createCrawler()
        ->setCrawlProfile(new CrawlInternalUrls('localhost:8080'))
        ->startCrawling('http://localhost:8080');

    $urls = [
        ['url' => 'http://localhost:8080/link1', 'foundOn' => 'http://localhost:8080/'],
        ['url' => 'http://example.com/'],
    ];

    expect($urls)->sequence(
        function ($url) {
            $url->toBeCrawledOnce();
        },
        function ($url) {
            $url->notToBeCrawled();
        },
    );
});

it('can handle pages with invalid urls', function () {
    $crawlProfile = new class () extends CrawlProfile {
        public function shouldCrawl(UriInterface $url): bool
        {
            return true;
        }
    };

    createCrawler()
        ->setCrawlProfile($crawlProfile)
        ->startCrawling('localhost:8080/invalid-url');

    expect(['url' => 'http://localhost:8080/invalid-url'])
        ->toBeCrawledOnce();
});

it('respects the total crawl limit', function () {
    foreach (range(1, 8) as $maximumCrawlCount) {
        Log::reset();

        createCrawler()
            ->setTotalCrawlLimit($maximumCrawlCount)
            ->ignoreRobots()
            ->setCrawlProfile(new CrawlInternalUrls('localhost:8080'))
            ->startCrawling('http://localhost:8080');

        assertCrawledUrlCount($maximumCrawlCount);
    }
});

it('respects the current crawl limit', function () {
    foreach (range(1, 8) as $maximumCrawlCount) {
        Log::reset();

        createCrawler()
            ->setCurrentCrawlLimit($maximumCrawlCount)
            ->ignoreRobots()
            ->setCrawlProfile(new CrawlInternalUrls('localhost:8080'))
            ->startCrawling('http://localhost:8080');

        assertCrawledUrlCount($maximumCrawlCount);
    }
});

it('respects current before total limit', function () {
    foreach (range(1, 8) as $maximumCrawlCount) {
        Log::reset();

        createCrawler()
            ->setCurrentCrawlLimit(4)
            ->setTotalCrawlLimit($maximumCrawlCount)
            ->ignoreRobots()
            ->setCrawlProfile(new CrawlInternalUrls('localhost:8080'))
            ->startCrawling('http://localhost:8080');

        assertCrawledUrlCount($maximumCrawlCount > 4 ? 4 : $maximumCrawlCount);
    }
});

it('doesnt extract links if the crawled page exceeds the maximum response size', function () {
    createCrawler()
        ->setMaximumResponseSize(10)
        ->startCrawling('http://localhost:8080');

    expect(['url' => 'http://localhost:8080/'])
        ->toBeCrawledOnce();

    expect([
        ['url' => 'http://localhost:8080/link1', 'foundOn' => 'http://localhost:8080/'],
        ['url' => 'http://localhost:8080/link2', 'foundOn' => 'http://localhost:8080/'],
        ['url' => 'http://localhost:8080/dir/link4', 'foundOn' => 'http://localhost:8080/'],
    ])->each->notToBeCrawled();
});

it('will crawl to specified depth', function () {
    createCrawler()
        ->setMaximumDepth(1)
        ->startCrawling('http://localhost:8080');

    expect([
        ['url' => 'http://localhost:8080/'],
        ['url' => 'http://localhost:8080/link1', 'foundOn' => 'http://localhost:8080/'],
        ['url' => 'http://localhost:8080/link2', 'foundOn' => 'http://localhost:8080/'],
        ['url' => 'http://localhost:8080/dir/link4', 'foundOn' => 'http://localhost:8080/'],
    ])->each->toBeCrawledOnce();

    expect([
        ['url' => 'http://example.com/'],
        ['url' => 'http://localhost:8080/link3'],
        ['url' => 'http://localhost:8080/notExists'],
        ['url' => 'http://localhost:8080/dir/link5'],
        ['url' => 'http://localhost:8080/dir/subdir/link5'],
    ])->each->notToBeCrawled();

    Log::reset();

    createCrawler()
        ->setMaximumDepth(2)
        ->startCrawling('http://localhost:8080');

    expect([
        ['url' => 'http://localhost:8080/'],
        ['url' => 'http://localhost:8080/link1', 'foundOn' => 'http://localhost:8080/'],
        ['url' => 'http://localhost:8080/link2', 'foundOn' => 'http://localhost:8080/'],
        ['url' => 'http://localhost:8080/dir/link4', 'foundOn' => 'http://localhost:8080/'],
        ['url' => 'http://example.com/', 'foundOn' => 'http://localhost:8080/link1'],
        ['url' => 'http://localhost:8080/link3', 'foundOn' => 'http://localhost:8080/link2'],
        ['url' => 'http://localhost:8080/dir/link5', 'foundOn' => 'http://localhost:8080/dir/link4'],
    ])->each->toBeCrawledOnce();

    expect([
        ['url' => 'http://localhost:8080/notExists'],
        ['url' => 'http://localhost:8080/dir/link5'],
        ['url' => 'http://localhost:8080/dir/subdir/link5'],
    ])->each->notToBeCrawled();
});

test('profile crawls a domain and its subdomains', function () {
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
        expect($profile->isSubdomainOfHost(new Uri($url)))
            ->toBe($bool);
    }
});

it('crawls subdomains', function () {
    $baseUrl = 'http://localhost:8080';

    createCrawler()
        ->setMaximumDepth(2)
        ->setCrawlProfile(new CrawlSubdomains($baseUrl))
        ->startCrawling($baseUrl);

    expect([
        ['url' => 'http://localhost:8080/'],
        ['url' => 'http://localhost:8080/link1', 'foundOn' => 'http://localhost:8080/'],
        ['url' => 'http://localhost:8080/link2', 'foundOn' => 'http://localhost:8080/'],
        ['url' => 'http://localhost:8080/dir/link4', 'foundOn' => 'http://localhost:8080/'],
        ['url' => 'http://localhost:8080/link3', 'foundOn' => 'http://localhost:8080/link2'],
        ['url' => 'http://localhost:8080/dir/link5', 'foundOn' => 'http://localhost:8080/dir/link4'],
        ['url' => 'http://sub.localhost:8080/subdomainpage', 'foundOn' => 'http://localhost:8080/link2'],
        ['url' => 'http://subdomain.sub.localhost:8080/subdomainpage', 'foundOn' => 'http://localhost:8080/link2'],
    ])->each->toBeCrawledOnce();

    expect([
        ['url' => 'http://localhost:8080/notExists'],
        ['url' => 'http://localhost:8080/dir/link5'],
        ['url' => 'http://localhost:8080/dir/subdir/link5'],
        ['url' => 'http://example.com/', 'foundOn' => 'http://localhost:8080/link1'],
    ])->each->notToBeCrawled();
});

it('should not follow nofollow links', function () {
    createCrawler()
        ->setMaximumDepth(1)
        ->startCrawling('http://localhost:8080');

    expect(['url' => 'http://localhost:8080/nofollow', 'foundOn' => 'http://localhost:8080/'])
        ->notToBeCrawled();
});

it('should handle redirects correctly when tracking is active', function () {
    createCrawler([
        RequestOptions::ALLOW_REDIRECTS => [
            'track_redirects' => true,
        ],
    ])->startCrawling('http://localhost:8080/dir1/internal-redirect-entry/');

    assertCrawledUrlCount(3);
});

it('respects the requested delay between requests', function () {
    $baseUrl = 'http://localhost:8080';

    $start = time();

    createCrawler()
        ->setMaximumDepth(2)
        ->setDelayBetweenRequests(500) // 500ms
        ->setCrawlProfile(new CrawlSubdomains($baseUrl))
        ->startCrawling($baseUrl);

    $end = time();

    $diff = $end - $start;

    // At 500ms delay per URL, crawling 8 URLs should take at least 4 seconds.
    expect($diff)->toBeGreaterThan(4);
});

test('custom crawl request handlers must extend abstracts', function () {
    Crawler::create()->setCrawlFulfilledHandlerClass(stdClass::class);

    Crawler::create()->setCrawlFailedHandlerClass(stdClass::class);
})->throws(InvalidCrawlRequestHandler::class);

it('should ignore user agents header case', function () {
    $clientConfig = ['headers' => ['user-agent' => 'foo']];
    $newUserAgent = 'bar';

    $crawler = Crawler::create($clientConfig)->setUserAgent($newUserAgent);
    $actualUserAgent = $crawler->getUserAgent();

    expect($actualUserAgent)->toBe($newUserAgent);
});

it('will only crawl correct mime types when asked to', function () {
    createCrawler()
        ->setParseableMimeTypes(['text/html', 'text/plain'])
        ->startCrawling('http://localhost:8080/content-types');

    $urls = [
        ['url' => 'http://localhost:8080/content-types/music.html', 'foundOn' => 'http://localhost:8080/content-types/music.mp3'],
        ['url' => 'http://localhost:8080/content-types/video.html', 'foundOn' => 'http://localhost:8080/content-types/video.mkv'],
        ['url' => 'http://localhost:8080/content-types/normal.html', 'foundOn' => 'http://localhost:8080/content-types'],
    ];

    expect($urls)->sequence(
        function ($url) {
            $url->notToBeCrawled();
        },
        function ($url) {
            $url->notToBeCrawled();
        },
        function ($url) {
            $url->toBeCrawledOnce();
        },
    );

    assertCrawledUrlCount(4);
});

it('will crawl all content types when not explicitly whitelisted', function () {
    createCrawler()
        ->startCrawling('http://localhost:8080/content-types');

    expect([
        ['url' => 'http://localhost:8080/content-types/music.html', 'foundOn' => 'http://localhost:8080/content-types/music.mp3'],
        ['url' => 'http://localhost:8080/content-types/video.html', 'foundOn' => 'http://localhost:8080/content-types/video.mkv'],
    ])->each->toBeCrawledOnce();

    assertCrawledUrlCount(6);
});

it('will allow streaming responses when the client asks for it', function () {
    $clientConfig = ['stream' => true];

    createCrawler($clientConfig)->startCrawling('http://localhost:8080/content-types');

    expect([
        ['url' => 'http://localhost:8080/content-types/music.html', 'foundOn' => 'http://localhost:8080/content-types/music.mp3'],
        ['url' => 'http://localhost:8080/content-types/video.html', 'foundOn' => 'http://localhost:8080/content-types/video.mkv'],
    ])->each->toBeCrawledOnce();

    assertCrawledUrlCount(6);
});

it('will not crawl half parsed href tags', function () {
    createCrawler()->startCrawling('http://localhost:8080/incomplete-href');

    expect(['url' => 'http://localhost:8080/invalid-link', 'foundOn' => 'http://localhost:8080/incomplete-href'])
        ->notToBeCrawled();

    assertCrawledUrlCount(3);
});

function javascriptInjectedUrls(): array
{
    return [[
        'url' => 'http://localhost:8080/javascript',
        'foundOn' => 'http://localhost:8080/link1',
    ]];
}

function regularUrls(): array
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
