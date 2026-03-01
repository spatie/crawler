<?php

use Spatie\Crawler\UrlParsers\LinkUrlParser;

it('marks urls with control characters as malformed', function () {
    $parser = new LinkUrlParser;

    $html = "<html><body><a href=\"https://example.com/path\ttrap\">bad link</a><a href=\"https://example.com/normal\">normal</a></body></html>";
    $urls = $parser->extractUrls($html, 'https://example.com');

    $malformed = array_values(array_filter($urls, fn ($u) => $u->isMalformed()));
    expect($malformed)->not->toBeEmpty();
    expect($malformed[0]->malformedReason)->toBe('URL contains control characters');

    $normalUrls = array_values(array_filter($urls, fn ($u) => ! $u->isMalformed()));
    expect($normalUrls)->not->toBeEmpty();
    expect($normalUrls[0]->url)->toContain('example.com/normal');
});

it('marks urls with vertical tab as malformed', function () {
    $parser = new LinkUrlParser;

    $html = "<html><body><a href=\"https://example.com/path\x0binject\">bad</a></body></html>";
    $urls = $parser->extractUrls($html, 'https://example.com');

    $malformed = array_values(array_filter($urls, fn ($u) => $u->isMalformed()));
    expect($malformed)->not->toBeEmpty();
    expect($malformed[0]->malformedReason)->toBe('URL contains control characters');
});

it('normalizes dot segments in extracted urls', function () {
    $parser = new LinkUrlParser;

    $html = '<html><body><a href="https://example.com/a/b/../c">link</a></body></html>';
    $urls = $parser->extractUrls($html, 'https://example.com');

    expect($urls)->not->toBeEmpty();
    expect($urls[0]->url)->toBe('https://example.com/a/c');
});

it('normalizes current directory dot segments', function () {
    $parser = new LinkUrlParser;

    $html = '<html><body><a href="https://example.com/a/./b/./c">link</a></body></html>';
    $urls = $parser->extractUrls($html, 'https://example.com');

    expect($urls)->not->toBeEmpty();
    expect($urls[0]->url)->toBe('https://example.com/a/b/c');
});

it('normalizes multiple parent dot segments', function () {
    $parser = new LinkUrlParser;

    $html = '<html><body><a href="https://example.com/a/b/c/../../d">link</a></body></html>';
    $urls = $parser->extractUrls($html, 'https://example.com');

    expect($urls)->not->toBeEmpty();
    expect($urls[0]->url)->toBe('https://example.com/a/d');
});

it('preserves userinfo when normalizing dot segments', function () {
    $parser = new LinkUrlParser;

    $html = '<html><body><a href="https://user:pass@example.com/a/b/../c">link</a></body></html>';
    $urls = $parser->extractUrls($html, 'https://example.com');

    expect($urls)->not->toBeEmpty();
    expect($urls[0]->url)->toBe('https://user:pass@example.com/a/c');
});

it('leaves urls without dot segments unchanged', function () {
    $parser = new LinkUrlParser;

    $html = '<html><body><a href="https://example.com/a/b/c">link</a></body></html>';
    $urls = $parser->extractUrls($html, 'https://example.com');

    expect($urls)->not->toBeEmpty();
    expect($urls[0]->url)->toBe('https://example.com/a/b/c');
});
