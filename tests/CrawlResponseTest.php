<?php

use GuzzleHttp\Psr7\Response;
use Spatie\Crawler\CrawlResponse;

it('can get the status code', function () {
    $response = new CrawlResponse(new Response(200));

    expect($response->status())->toBe(200);
});

it('can get the body', function () {
    $response = new CrawlResponse(new Response(200, [], 'Hello World'));

    expect($response->body())->toBe('Hello World');
});

it('caches the body', function () {
    $response = new CrawlResponse(new Response(200, [], 'Hello World'));

    $response->body();
    expect($response->body())->toBe('Hello World');
});

it('can get a header', function () {
    $response = new CrawlResponse(new Response(200, ['Content-Type' => 'text/html']));

    expect($response->header('Content-Type'))->toBe('text/html');
    expect($response->header('Non-Existent'))->toBeNull();
});

it('can get all headers', function () {
    $response = new CrawlResponse(new Response(200, ['Content-Type' => 'text/html', 'X-Custom' => 'value']));

    expect($response->headers())->toHaveKeys(['Content-Type', 'X-Custom']);
});

it('can get a dom crawler', function () {
    $response = new CrawlResponse(new Response(200, [], '<html><body><h1>Test</h1></body></html>'));

    $dom = $response->dom();

    expect($dom->filter('h1')->text())->toBe('Test');
});

it('can determine if a response is successful', function () {
    expect((new CrawlResponse(new Response(200)))->isSuccessful())->toBeTrue();
    expect((new CrawlResponse(new Response(201)))->isSuccessful())->toBeTrue();
    expect((new CrawlResponse(new Response(404)))->isSuccessful())->toBeFalse();
    expect((new CrawlResponse(new Response(500)))->isSuccessful())->toBeFalse();
});

it('can determine if a response is a redirect', function () {
    expect((new CrawlResponse(new Response(301)))->isRedirect())->toBeTrue();
    expect((new CrawlResponse(new Response(302)))->isRedirect())->toBeTrue();
    expect((new CrawlResponse(new Response(200)))->isRedirect())->toBeFalse();
});

it('can get the found on url', function () {
    $response = new CrawlResponse(new Response(200), 'https://example.com');

    expect($response->foundOnUrl())->toBe('https://example.com');
});

it('can get the link text', function () {
    $response = new CrawlResponse(new Response(200), null, 'Click here');

    expect($response->linkText())->toBe('Click here');
});

it('can get the depth', function () {
    $response = new CrawlResponse(new Response(200), null, null, 3);

    expect($response->depth())->toBe(3);
});

it('can get the psr response', function () {
    $psrResponse = new Response(200);
    $response = new CrawlResponse($psrResponse);

    expect($response->toPsrResponse())->toBe($psrResponse);
});

it('can create a fake response', function () {
    $response = CrawlResponse::fake('<html>Test</html>', 200, ['X-Custom' => 'value']);

    expect($response->status())->toBe(200);
    expect($response->body())->toBe('<html>Test</html>');
    expect($response->header('X-Custom'))->toBe('value');
});

it('can set a cached body', function () {
    $response = new CrawlResponse(new Response(200, [], 'original'));

    $response->setCachedBody('cached');

    expect($response->body())->toBe('cached');
});
