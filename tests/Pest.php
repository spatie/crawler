<?php

use PHPUnit\Framework\Assert;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlResponse;
use Spatie\Crawler\Test\TestClasses\CrawlLogger;
use Spatie\Crawler\Test\TestClasses\Log;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertStringNotContainsString;

expect()->extend('toBeNotEmpty', function () {
    Assert::assertNotEmpty($this->value);

    return $this;
});

expect()->extend('notToBeCrawled', function () {
    $url = $this->value;

    $logMessage = "hasBeenCrawled: {$url['url']}";

    if (isset($url['foundOn'])) {
        $logMessage .= " - found on {$url['foundOn']}";
    }

    $logMessage .= PHP_EOL;

    assertStringNotContainsString(
        $logMessage,
        Log::getContents(),
        "Did find {$logMessage} in the log"
    );
});

expect()->extend('toBeCrawledOnce', function () {
    $logContent = Log::getContents();

    $url = $this->value;

    if (! isset($url['linkText'])) {
        $logContent = preg_replace('/ - link text .*/', '', $logContent);
    }

    $logMessage = "hasBeenCrawled: {$url['url']}";

    if (isset($url['foundOn'])) {
        $logMessage .= " - found on {$url['foundOn']}";
    }

    if (isset($url['linkText'])) {
        $logMessage .= " - link text {$url['linkText']}";
    }

    $logMessage .= PHP_EOL;

    assertEquals(
        1,
        substr_count($logContent, $logMessage),
        "Did not find {$logMessage} exactly one time in the log but ".substr_count($logContent, $logMessage)." times. Contents of log\n{$logContent}"
    );
});

function expectCrawledUrlCount(int $count): void
{
    $logContent = Log::getContents();

    $actualCount = substr_count($logContent, 'hasBeenCrawled');

    assertEquals($count, $actualCount, "Crawled `{$actualCount}` urls instead of the expected {$count}");
}

function createCrawler(string $url = 'https://example.com', $options = []): Crawler
{
    return Crawler::create($url, $options)
        ->depth(3)
        ->addObserver(new CrawlLogger);
}

function fullSiteFakes(): array
{
    return [
        'https://example.com/robots.txt' => robotsTxtContent(),
        'https://example.com' => '<a href="/txt-disallow">txt disallowed</a><a href="/meta-follow">meta disallowed</a><a href="/header-disallow">header disallowed</a><a href="/link1">Link1</a><a href="/link2">Link2</a><a href="dir/link4">Link4</a><a href="mailto:test@example.com">Email</a><a href="tel:123">Telephone</a><a href="/nofollow" rel="nofollow">No follow</a><a href="/txt-disallow-custom-user-agent">Disallow Custom User Agent</a>',
        'https://example.com/link1' => '<html><head><link rel="next" href="/link1-next"><link rel="prev" href="/link1-prev"></head><body><script>var url = \'/javascript\';document.body.innerHTML = document.body.innerHTML + "<a href=\'" + url + "\'>Javascript Link</a>"</script>You are on link1<a href="https://external.example.org/">External Link</a></body></html>',
        'https://example.com/javascript' => 'This page can only be reached if JavaScript is being executed',
        'https://example.com/link1-next' => 'You are on link1-next. Next page of link1',
        'https://example.com/link1-prev' => 'You are on link1-prev. Previous page of link1',
        'https://example.com/link-with-html' => '<a href="/link1"><div title="some title"><div>Link text inner</div></div></a>',
        'https://example.com/nofollow' => 'This page should not be crawled',
        'https://example.com/link2' => 'You are on link2<a href="/link3">Link3</a><a href="https://sub.example.com/subdomainpage">Subdomain</a><a href="https://subdomain.sub.example.com/subdomainpage">Subdomain2</a>',
        'https://example.com/link3' => 'You are on link3<a href="/notExists">not exists</a>',
        'https://example.com/dir/link4' => 'You are on /dir/link4<a href="link5">link 5</a>',
        'https://example.com/dir/link5' => 'You are on /dir/link5<a href="subdir/link6">link 6</a>',
        'https://example.com/dir/subdir/link6' => 'You are on /dir/subdir/link6<a href="/link1">link 1</a>',
        'https://example.com/invalid-url' => 'There is an <a href="https:///AfyaVzw">invalid</a> url',
        'https://example.com/txt-disallow' => 'Not allowed',
        'https://example.com/txt-disallow-custom-user-agent' => 'Not allowed for Custom User Agent',
        'https://example.com/meta-follow' => '<html><head>'."\n".'<meta name="robots" content="noindex, follow">'."\n".'</head><body><a href="/meta-nofollow">No follow</a></body></html>',
        'https://example.com/meta-nofollow' => '<html><head>'."\n".'<meta name="robots" content="index, nofollow">'."\n".'</head><body><a href="/meta-nofollow-target">no follow it</a></body></html>',
        'https://example.com/meta-nofollow-target' => 'No followable',
        'https://example.com/header-disallow' => CrawlResponse::fake('disallow by header', 200, ['X-Robots-Tag' => '*: noindex']),
        'https://example.com/incomplete-href' => 'Valid href: <a href="/link1-next">valid link</a>, Empty href: <a href="/link1-prev"></a>, Incomplete href: <a href="/invalid-link',
        'https://external.example.org/' => 'External site',
        'https://sub.example.com/subdomainpage' => 'Subdomain page',
        'https://subdomain.sub.example.com/subdomainpage' => 'Subdomain 2 page',
    ];
}

function robotsTxtContent(): string
{
    return "User-agent: *\nDisallow: /txt-disallow\nUser-agent: my-agent\nDisallow: /txt-disallow\nDisallow: /txt-disallow-custom-user-agent";
}

function contentTypeFakes(): array
{
    return [
        'https://example.com/content-types' => 'We have <a href="/content-types/normal.html">a normal page</a>, <a href="/content-types/music.mp3">an MP3</a> and <a href="/content-types/video.mkv">a video file</a>.',
        'https://example.com/content-types/normal.html' => CrawlResponse::fake('a normal HTML file', 200, ['Content-Type' => 'text/html; charset=utf-8']),
        'https://example.com/content-types/music.mp3' => CrawlResponse::fake('music file, with a <a href="/content-types/music.html">a link</a>', 200, ['Content-Type' => 'audio/mpeg']),
        'https://example.com/content-types/music.html' => 'hidden html in music file',
        'https://example.com/content-types/video.mkv' => CrawlResponse::fake('video file, with a <a href="/content-types/video.html">a link</a>', 200, ['Content-Type' => 'video/webm']),
        'https://example.com/content-types/video.html' => 'hidden html in video file',
    ];
}

function sitemapFakes(): array
{
    $sitemapIndex = '<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
   <sitemap>
       <loc>https://example.com/sitemap1.xml</loc>
       <lastmod>2024-01-01</lastmod>
   </sitemap>
   <sitemap>
       <loc>https://example.com/sitemap2.xml</loc>
       <lastmod>2024-01-01</lastmod>
   </sitemap>
</sitemapindex>';

    $sitemap1 = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
   <url>
       <loc>https://example.com/</loc>
       <lastmod>2016-01-01</lastmod>
       <changefreq>monthly</changefreq>
       <priority>0.8</priority>
   </url>
   <url>
       <loc>https://example.com/link1</loc>
       <lastmod>2016-01-01</lastmod>
       <changefreq>monthly</changefreq>
       <priority>0.8</priority>
   </url>
</urlset>';

    $sitemap2 = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
   <url>
       <loc>https://example.com/link1-next</loc>
       <lastmod>2016-01-01</lastmod>
       <changefreq>monthly</changefreq>
       <priority>0.8</priority>
   </url>
   <url>
       <loc>https://example.com/link1-prev</loc>
       <lastmod>2016-01-01</lastmod>
       <changefreq>monthly</changefreq>
       <priority>0.8</priority>
   </url>
   <url>
       <loc>https://example.com/link2</loc>
       <lastmod>2016-01-01</lastmod>
       <changefreq>monthly</changefreq>
       <priority>0.8</priority>
   </url>
   <url lang="fr">
       <loc>https://example.com/link3</loc>
       <lastmod>2016-01-01</lastmod>
       <changefreq>monthly</changefreq>
       <priority>0.8</priority>
   </url>
</urlset>';

    return [
        'https://example.com/sitemap_index.xml' => CrawlResponse::fake($sitemapIndex, 200, ['Content-Type' => 'text/xml; charset=utf-8']),
        'https://example.com/sitemap1.xml' => CrawlResponse::fake($sitemap1, 200, ['Content-Type' => 'text/xml; charset=utf-8']),
        'https://example.com/sitemap2.xml' => CrawlResponse::fake($sitemap2, 200, ['Content-Type' => 'text/xml; charset=utf-8']),
        'https://example.com/' => 'Homepage',
        'https://example.com/link1' => 'Link 1',
        'https://example.com/link1-next' => 'Link 1 next',
        'https://example.com/link1-prev' => 'Link 1 prev',
        'https://example.com/link2' => 'Link 2',
        'https://example.com/link3' => 'Link 3',
    ];
}
