<?php

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\RequestOptions;
use Spatie\Crawler\Crawler;

it('can set basic auth credentials', function () {
    $crawler = Crawler::create('https://example.com')
        ->basicAuth('user', 'secret');

    $config = getClientConfig($crawler);

    expect($config[RequestOptions::AUTH])->toBe(['user', 'secret']);
});

it('can set a bearer token', function () {
    $crawler = Crawler::create('https://example.com')
        ->token('my-token');

    $config = getClientConfig($crawler);

    expect($config[RequestOptions::HEADERS]['Authorization'])->toBe('Bearer my-token');
});

it('can set a token with a custom type', function () {
    $crawler = Crawler::create('https://example.com')
        ->token('my-token', 'Token');

    $config = getClientConfig($crawler);

    expect($config[RequestOptions::HEADERS]['Authorization'])->toBe('Token my-token');
});

it('can disable ssl verification', function () {
    $crawler = Crawler::create('https://example.com')
        ->withoutVerifying();

    $config = getClientConfig($crawler);

    expect($config[RequestOptions::VERIFY])->toBeFalse();
});

it('can set a proxy', function () {
    $crawler = Crawler::create('https://example.com')
        ->proxy('http://proxy:8080');

    $config = getClientConfig($crawler);

    expect($config[RequestOptions::PROXY])->toBe('http://proxy:8080');
});

it('can set cookies', function () {
    $crawler = Crawler::create('https://example.com')
        ->cookies(['session' => 'abc123'], 'example.com');

    $config = getClientConfig($crawler);

    expect($config[RequestOptions::COOKIES])->toBeInstanceOf(CookieJar::class);
    expect($config[RequestOptions::COOKIES]->getCookieByName('session')->getValue())->toBe('abc123');
});

it('can set query parameters', function () {
    $crawler = Crawler::create('https://example.com')
        ->queryParameters(['api_key' => 'xyz']);

    $config = getClientConfig($crawler);

    expect($config[RequestOptions::QUERY])->toBe(['api_key' => 'xyz']);
});

it('can merge query parameters across multiple calls', function () {
    $crawler = Crawler::create('https://example.com')
        ->queryParameters(['api_key' => 'xyz'])
        ->queryParameters(['lang' => 'en']);

    $config = getClientConfig($crawler);

    expect($config[RequestOptions::QUERY])->toBe(['api_key' => 'xyz', 'lang' => 'en']);
});

it('can combine multiple request configuration methods', function () {
    $crawler = Crawler::create('https://example.com')
        ->basicAuth('user', 'secret')
        ->withoutVerifying()
        ->proxy('http://proxy:8080')
        ->queryParameters(['key' => 'value']);

    $config = getClientConfig($crawler);

    expect($config[RequestOptions::AUTH])->toBe(['user', 'secret']);
    expect($config[RequestOptions::VERIFY])->toBeFalse();
    expect($config[RequestOptions::PROXY])->toBe('http://proxy:8080');
    expect($config[RequestOptions::QUERY])->toBe(['key' => 'value']);
});

function getClientConfig(Crawler $crawler): array
{
    $buildClient = new ReflectionMethod($crawler, 'buildClient');
    $client = $buildClient->invoke($crawler);

    return $client->getConfig();
}
