<?php

namespace Spatie\Crawler\Test;

use GuzzleHttp\RequestOptions;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlProfiles\CrawlInternalUrls;
use Spatie\Crawler\CrawlProfiles\CrawlProfile;
use Spatie\Crawler\CrawlProfiles\CrawlSubdomains;
use Spatie\Crawler\CrawlResponse;
use Spatie\Crawler\Exceptions\InvalidCrawlRequestHandler;
use Spatie\Crawler\Test\TestClasses\CrawlLogger;
use Spatie\Crawler\Test\TestClasses\Log;
use stdClass;

beforeEach(function () {
    Log::reset();
});

it('will crawl all found urls', function () {
    createCrawler()
        ->fake(fullSiteFakes())
        ->start();

    expect(regularUrls())->each->toBeCrawledOnce();

    expect(javascriptInjectedUrls())->each->notToBeCrawled();
});

it('will not crawl tel links', function () {
    createCrawler()
        ->fake(fullSiteFakes())
        ->start();

    expect(['url' => 'https://example.com/tel:123', 'foundOn' => 'https://example.com/'])
        ->notToBeCrawled();
});

it('will handle multiple observers', function () {
    Crawler::create('https://example.com')
        ->fake(fullSiteFakes())
        ->addObserver(new CrawlLogger('Observer A'))
        ->addObserver(new CrawlLogger('Observer B'))
        ->start();

    expect(Log::getContents())
        ->toContain('Observer A')
        ->toContain('Observer B');
});

it('can crawl uris without scheme', function () {
    createCrawler('example.com')
        ->fake(fullSiteFakes())
        ->defaultScheme('https')
        ->start();

    expect(regularUrls())->each->toBeCrawledOnce();
});

it('uses a crawl profile to determine what should be crawled', function () {
    $crawlProfile = new class implements CrawlProfile
    {
        public function shouldCrawl(string $url): bool
        {
            return parse_url($url, PHP_URL_PATH) !== '/link3';
        }
    };

    createCrawler()
        ->fake(fullSiteFakes())
        ->setCrawlProfile($crawlProfile)
        ->start();

    expect([
        ['url' => 'https://example.com/'],
        ['url' => 'https://example.com/link1', 'foundOn' => 'https://example.com/'],
        ['url' => 'https://example.com/link2', 'foundOn' => 'https://example.com/'],
    ])->each->toBeCrawledOnce();

    expect(['url' => 'https://example.com/link3'])->notToBeCrawled();
});

it('will pass the correct link texts', function () {
    createCrawler()
        ->fake(fullSiteFakes())
        ->start();

    expect([
        ['url' => 'https://example.com/'],
        ['url' => 'https://example.com/link1', 'foundOn' => 'https://example.com/', 'linkText' => 'Link1'],
        ['url' => 'https://example.com/link2', 'foundOn' => 'https://example.com/', 'linkText' => 'Link2'],
    ])->each->toBeCrawledOnce();
});

it('will get the text from a html link', function () {
    createCrawler('https://example.com/link-with-html')
        ->fake(fullSiteFakes())
        ->start();

    expect([
        'url' => 'https://example.com/link1',
        'foundOn' => 'https://example.com/link-with-html',
        'linkText' => 'Link text inner',
    ])->toBeCrawledOnce();
});

it('uses crawl profile for internal urls', function () {
    createCrawler()
        ->fake(fullSiteFakes())
        ->setCrawlProfile(new CrawlInternalUrls('https://example.com'))
        ->start();

    expect(['url' => 'https://example.com/link1', 'foundOn' => 'https://example.com/', 'linkText' => 'Link1'])
        ->toBeCrawledOnce();

    expect(['url' => 'https://external.example.org/'])
        ->notToBeCrawled();
});

it('can handle pages with invalid urls', function () {
    $crawlProfile = new class implements CrawlProfile
    {
        public function shouldCrawl(string $url): bool
        {
            return true;
        }
    };

    createCrawler('https://example.com/invalid-url')
        ->fake(fullSiteFakes())
        ->setCrawlProfile($crawlProfile)
        ->start();

    expect(['url' => 'https://example.com/invalid-url'])
        ->toBeCrawledOnce();
});

it('respects the total crawl limit', function () {
    foreach (range(1, 8) as $maximumCrawlCount) {
        Log::reset();

        createCrawler()
            ->fake(fullSiteFakes())
            ->limit($maximumCrawlCount)
            ->ignoreRobots()
            ->setCrawlProfile(new CrawlInternalUrls('https://example.com'))
            ->start();

        expectCrawledUrlCount($maximumCrawlCount);
    }
});

it('respects the current crawl limit', function () {
    foreach (range(1, 8) as $maximumCrawlCount) {
        Log::reset();

        createCrawler()
            ->fake(fullSiteFakes())
            ->limitPerExecution($maximumCrawlCount)
            ->ignoreRobots()
            ->setCrawlProfile(new CrawlInternalUrls('https://example.com'))
            ->start();

        expectCrawledUrlCount($maximumCrawlCount);
    }
});

it('respects current before total limit', function () {
    foreach (range(1, 8) as $maximumCrawlCount) {
        Log::reset();

        createCrawler()
            ->fake(fullSiteFakes())
            ->limitPerExecution(4)
            ->limit($maximumCrawlCount)
            ->ignoreRobots()
            ->setCrawlProfile(new CrawlInternalUrls('https://example.com'))
            ->start();

        expectCrawledUrlCount($maximumCrawlCount > 4 ? 4 : $maximumCrawlCount);
    }
});

it('doesnt extract links if the crawled page exceeds the maximum response size', function () {
    createCrawler()
        ->fake(fullSiteFakes())
        ->maxResponseSize(10)
        ->start();

    expect(['url' => 'https://example.com/'])
        ->toBeCrawledOnce();

    expect([
        ['url' => 'https://example.com/link1', 'foundOn' => 'https://example.com/'],
        ['url' => 'https://example.com/link2', 'foundOn' => 'https://example.com/'],
        ['url' => 'https://example.com/dir/link4', 'foundOn' => 'https://example.com/'],
    ])->each->notToBeCrawled();
});

it('will crawl to specified depth', function () {
    createCrawler()
        ->fake(fullSiteFakes())
        ->depth(1)
        ->start();

    expect([
        ['url' => 'https://example.com/'],
        ['url' => 'https://example.com/link1', 'foundOn' => 'https://example.com/'],
        ['url' => 'https://example.com/link2', 'foundOn' => 'https://example.com/'],
        ['url' => 'https://example.com/dir/link4', 'foundOn' => 'https://example.com/'],
    ])->each->toBeCrawledOnce();

    expect([
        ['url' => 'https://external.example.org/'],
        ['url' => 'https://example.com/link3'],
        ['url' => 'https://example.com/notExists'],
        ['url' => 'https://example.com/dir/link5'],
        ['url' => 'https://example.com/dir/subdir/link5'],
    ])->each->notToBeCrawled();

    Log::reset();

    createCrawler()
        ->fake(fullSiteFakes())
        ->depth(2)
        ->start();

    expect([
        ['url' => 'https://example.com/'],
        ['url' => 'https://example.com/link1', 'foundOn' => 'https://example.com/'],
        ['url' => 'https://example.com/link2', 'foundOn' => 'https://example.com/'],
        ['url' => 'https://example.com/dir/link4', 'foundOn' => 'https://example.com/'],
        ['url' => 'https://external.example.org/', 'foundOn' => 'https://example.com/link1'],
        ['url' => 'https://example.com/link3', 'foundOn' => 'https://example.com/link2'],
        ['url' => 'https://example.com/dir/link5', 'foundOn' => 'https://example.com/dir/link4'],
    ])->each->toBeCrawledOnce();

    expect([
        ['url' => 'https://example.com/notExists'],
        ['url' => 'https://example.com/dir/link5'],
        ['url' => 'https://example.com/dir/subdir/link5'],
    ])->each->notToBeCrawled();
});

test('profile crawls a domain and its subdomains', function () {
    $baseUrl = 'https://spatie.be';

    $urls = [
        'https://spatie.be' => true,
        'https://subdomain.spatie.be' => true,
        'https://www.subdomain.spatie.be' => true,
        'https://sub.dom.ain.spatie.be' => true,
        'https://subdomain.example.com' => false,
        'https://example.com' => false,
    ];

    $profile = new CrawlSubdomains($baseUrl);

    foreach ($urls as $url => $bool) {
        expect($profile->isSubdomainOfHost($url))
            ->toBe($bool);
    }
});

it('crawls subdomains', function () {
    createCrawler()
        ->fake(fullSiteFakes())
        ->depth(2)
        ->setCrawlProfile(new CrawlSubdomains('https://example.com'))
        ->start();

    expect([
        ['url' => 'https://example.com/'],
        ['url' => 'https://example.com/link1', 'foundOn' => 'https://example.com/'],
        ['url' => 'https://example.com/link2', 'foundOn' => 'https://example.com/'],
        ['url' => 'https://example.com/dir/link4', 'foundOn' => 'https://example.com/'],
        ['url' => 'https://example.com/link3', 'foundOn' => 'https://example.com/link2'],
        ['url' => 'https://example.com/dir/link5', 'foundOn' => 'https://example.com/dir/link4'],
        ['url' => 'https://sub.example.com/subdomainpage', 'foundOn' => 'https://example.com/link2'],
        ['url' => 'https://subdomain.sub.example.com/subdomainpage', 'foundOn' => 'https://example.com/link2'],
    ])->each->toBeCrawledOnce();

    expect([
        ['url' => 'https://example.com/notExists'],
        ['url' => 'https://example.com/dir/link5'],
        ['url' => 'https://example.com/dir/subdir/link5'],
        ['url' => 'https://external.example.org/', 'foundOn' => 'https://example.com/link1'],
    ])->each->notToBeCrawled();
});

it('should not follow nofollow links', function () {
    createCrawler()
        ->fake(fullSiteFakes())
        ->depth(1)
        ->start();

    expect(['url' => 'https://example.com/nofollow', 'foundOn' => 'https://example.com/'])
        ->notToBeCrawled();
});

it('should handle redirects correctly when tracking is active', function () {
    $fakes = fullSiteFakes();
    $fakes['https://example.com/dir1/internal-redirect-entry/'] = '<a href="../loop-generator/internal-redirect/trapped/">trapped</a> <a href="../../dir1/internal-redirect/trap/">trap-start</a>';
    $fakes['https://example.com/dir1/internal-redirect/trap/'] = CrawlResponse::fake('', 301, ['Location' => 'https://example.com/dir1/internal-redirect-entry/']);
    $fakes['https://example.com/dir1/loop-generator/internal-redirect/trapped/'] = 'It should be crawled once';

    createCrawler('https://example.com/dir1/internal-redirect-entry/', [
        RequestOptions::ALLOW_REDIRECTS => [
            'track_redirects' => true,
        ],
    ])
        ->fake($fakes)
        ->start();

    expectCrawledUrlCount(3);
});

it('should handle redirects correctly when max depth is specified', function () {
    $fakes = fullSiteFakes();
    $fakes['https://example.com/redirect-home/'] = CrawlResponse::fake('', 301, ['Location' => 'https://example.com/']);

    createCrawler('https://example.com/redirect-home/', [
        RequestOptions::ALLOW_REDIRECTS => [
            'track_redirects' => true,
        ],
    ])
        ->fake($fakes)
        ->depth(5)
        ->start();

    expect(['url' => 'https://example.com/link1', 'foundOn' => 'https://example.com/'])->toBeCrawledOnce();
});

it('respects the requested delay between requests', function () {
    $start = time();

    createCrawler()
        ->fake(fullSiteFakes())
        ->depth(2)
        ->delay(500) // 500ms
        ->setCrawlProfile(new CrawlSubdomains('https://example.com'))
        ->start();

    $end = time();

    $diff = $end - $start;

    // At 500ms delay per URL, crawling multiple URLs should take several seconds.
    expect($diff)->toBeGreaterThan(4);
});

test('custom crawl request handlers must extend abstracts', function () {
    Crawler::create()->setCrawlFulfilledHandlerClass(stdClass::class);

    Crawler::create()->setCrawlFailedHandlerClass(stdClass::class);
})->throws(InvalidCrawlRequestHandler::class);

it('will only crawl correct mime types when asked to', function () {
    createCrawler('https://example.com/content-types')
        ->fake(contentTypeFakes())
        ->allowedMimeTypes(['text/html', 'text/plain'])
        ->start();

    expect([
        ['url' => 'https://example.com/content-types/music.mp3', 'foundOn' => 'https://example.com/content-types'],
        ['url' => 'https://example.com/content-types/video.mkv', 'foundOn' => 'https://example.com/content-types'],
    ])->each->notToBeCrawled();

    expect(['url' => 'https://example.com/content-types/normal.html', 'foundOn' => 'https://example.com/content-types'])
        ->toBeCrawledOnce();

    expectCrawledUrlCount(2);
});

it('will crawl all content types when not explicitly whitelisted', function () {
    createCrawler('https://example.com/content-types')
        ->fake(contentTypeFakes())
        ->start();

    expect([
        ['url' => 'https://example.com/content-types/music.html', 'foundOn' => 'https://example.com/content-types/music.mp3'],
        ['url' => 'https://example.com/content-types/video.html', 'foundOn' => 'https://example.com/content-types/video.mkv'],
    ])->each->toBeCrawledOnce();

    expectCrawledUrlCount(6);
});

it('will allow streaming responses when the client asks for it', function () {
    createCrawler('https://example.com/content-types', ['stream' => true])
        ->fake(contentTypeFakes())
        ->start();

    expect([
        ['url' => 'https://example.com/content-types/music.html', 'foundOn' => 'https://example.com/content-types/music.mp3'],
        ['url' => 'https://example.com/content-types/video.html', 'foundOn' => 'https://example.com/content-types/video.mkv'],
    ])->each->toBeCrawledOnce();

    expectCrawledUrlCount(6);
});

it('will not crawl half parsed href tags', function () {
    createCrawler('https://example.com/incomplete-href')
        ->fake(fullSiteFakes())
        ->start();

    expect(['url' => 'https://example.com/invalid-link', 'foundOn' => 'https://example.com/incomplete-href'])
        ->notToBeCrawled();

    expectCrawledUrlCount(3);
});

it('respects the total execution time limit', function () {
    $crawler = createCrawler()
        ->fake(fullSiteFakes())
        ->depth(2)
        ->delay(500) // 500ms
        ->timeLimit(2)
        ->setCrawlProfile(new CrawlSubdomains('https://example.com'));

    $crawler->start();

    // At 500ms delay per URL, only four URLs can be crawled in 2 seconds.
    expectCrawledUrlCount(4);

    $crawler->start();

    expectCrawledUrlCount(4);
});

it('respects the current execution time limit', function () {
    $crawler = createCrawler()
        ->fake(fullSiteFakes())
        ->depth(2)
        ->delay(500) // 500ms
        ->timeLimitPerExecution(2)
        ->setCrawlProfile(new CrawlSubdomains('https://example.com'));

    $crawler->start();

    // At 500ms delay per URL, only four URLs can be crawled in 2 seconds.
    expectCrawledUrlCount(4);

    $crawler->start();

    expectCrawledUrlCount(11);
});

it('should return the user agent', function () {
    $crawler = Crawler::create()
        ->userAgent('test/1.2.3');

    expect($crawler->getUserAgent())
        ->toBe('test/1.2.3');
});

it('should return default user agent when none is set', function () {
    expect(Crawler::create()->getUserAgent())
        ->not->toBeEmpty();
});

it('should change the default base url scheme to https', function () {
    $crawler = Crawler::create()
        ->defaultScheme('https');

    expect($crawler->getDefaultScheme())
        ->toEqual('https');
});

it('should remember settings', function () {
    $crawler = Crawler::create()
        ->depth(10)
        ->limit(10)
        ->userAgent('test/1.2.3');

    expect($crawler->getMaximumDepth())->toBe(10);
    expect($crawler->getTotalCrawlLimit())->toBe(10);
    expect($crawler->getUserAgent())->toBe('test/1.2.3');
});

function javascriptInjectedUrls(): array
{
    return [[
        'url' => 'https://example.com/javascript',
        'foundOn' => 'https://example.com/link1',
    ]];
}

function regularUrls(): array
{
    return [
        ['url' => 'https://example.com/'],
        ['url' => 'https://example.com/link1', 'foundOn' => 'https://example.com/'],
        ['url' => 'https://example.com/link1-prev', 'foundOn' => 'https://example.com/link1'],
        ['url' => 'https://example.com/link1-next', 'foundOn' => 'https://example.com/link1'],
        ['url' => 'https://example.com/link2', 'foundOn' => 'https://example.com/'],
        ['url' => 'https://example.com/link3', 'foundOn' => 'https://example.com/link2'],
        ['url' => 'https://example.com/notExists', 'foundOn' => 'https://example.com/link3'],
        ['url' => 'https://external.example.org/', 'foundOn' => 'https://example.com/link1'],
        ['url' => 'https://example.com/dir/link4', 'foundOn' => 'https://example.com/'],
        ['url' => 'https://example.com/dir/link5', 'foundOn' => 'https://example.com/dir/link4'],
        ['url' => 'https://example.com/dir/subdir/link6', 'foundOn' => 'https://example.com/dir/link5'],
    ];
}
