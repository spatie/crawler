<?php

use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlResponse;

$baseUrl = 'https://request-mirror.ohdear.app/crawler-test';

beforeAll(function () use ($baseUrl) {
    try {
        $response = @file_get_contents($baseUrl, false, stream_context_create([
            'http' => ['timeout' => 5],
        ]));

        if ($response === false) {
            throw new Exception('Unreachable');
        }
    } catch (Exception) {
        markTestSkipped('request-mirror.ohdear.app is not reachable');
    }
});

it('transmits custom user agent on every page', function () use ($baseUrl) {
    $results = [];

    Crawler::create($baseUrl)
        ->ignoreRobots()
        ->concurrency(1)
        ->userAgent('SpatieCrawlerTest/1.0')
        ->onCrawled(function (string $url, CrawlResponse $response) use (&$results) {
            $mirrorData = extractMirrorData($response);

            if ($mirrorData) {
                $results[$url] = $mirrorData;
            }
        })
        ->start();

    expect($results)->not->toBeEmpty();

    foreach ($results as $url => $data) {
        expect($data['headers']['user-agent'])->toBe('SpatieCrawlerTest/1.0', "User agent mismatch on {$url}");
    }
})->group('request-mirror');

it('transmits custom headers on every page', function () use ($baseUrl) {
    $results = [];

    Crawler::create($baseUrl)
        ->ignoreRobots()
        ->concurrency(1)
        ->headers(['X-Custom-Test' => 'test-value-123'])
        ->onCrawled(function (string $url, CrawlResponse $response) use (&$results) {
            $mirrorData = extractMirrorData($response);

            if ($mirrorData) {
                $results[$url] = $mirrorData;
            }
        })
        ->start();

    expect($results)->not->toBeEmpty();

    foreach ($results as $url => $data) {
        expect($data['headers']['x-custom-test'])->toBe('test-value-123', "Custom header missing on {$url}");
    }
})->group('request-mirror');

it('transmits basic auth credentials', function () use ($baseUrl) {
    $results = [];

    Crawler::create($baseUrl)
        ->ignoreRobots()
        ->concurrency(1)
        ->basicAuth('user', 'pass')
        ->onCrawled(function (string $url, CrawlResponse $response) use (&$results) {
            $mirrorData = extractMirrorData($response);

            if ($mirrorData) {
                $results[$url] = $mirrorData;
            }
        })
        ->start();

    expect($results)->not->toBeEmpty();

    $expectedAuth = 'Basic '.base64_encode('user:pass');

    foreach ($results as $url => $data) {
        expect($data['headers']['authorization'])->toBe($expectedAuth, "Basic auth missing on {$url}");
    }
})->group('request-mirror');

it('transmits bearer token', function () use ($baseUrl) {
    $results = [];

    Crawler::create($baseUrl)
        ->ignoreRobots()
        ->concurrency(1)
        ->token('my-test-token')
        ->onCrawled(function (string $url, CrawlResponse $response) use (&$results) {
            $mirrorData = extractMirrorData($response);

            if ($mirrorData) {
                $results[$url] = $mirrorData;
            }
        })
        ->start();

    expect($results)->not->toBeEmpty();

    foreach ($results as $url => $data) {
        expect($data['headers']['authorization'])->toBe('Bearer my-test-token', "Bearer token missing on {$url}");
    }
})->group('request-mirror');

it('appends query parameters on every page', function () use ($baseUrl) {
    $results = [];

    Crawler::create($baseUrl)
        ->ignoreRobots()
        ->concurrency(1)
        ->queryParameters(['test_param' => 'test_value'])
        ->onCrawled(function (string $url, CrawlResponse $response) use (&$results) {
            $mirrorData = extractMirrorData($response);

            if ($mirrorData) {
                $results[$url] = $mirrorData;
            }
        })
        ->start();

    expect($results)->not->toBeEmpty();

    foreach ($results as $url => $data) {
        expect($data['args'])->toHaveKey('test_param', 'test_value');
    }
})->group('request-mirror');

function extractMirrorData(CrawlResponse $response): ?array
{
    $body = $response->body();

    if (! preg_match('/<script type="application\/json" id="request-mirror-data">(.*?)<\/script>/s', $body, $matches)) {
        return null;
    }

    return json_decode($matches[1], true);
}
