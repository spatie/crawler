<?php

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\TransferStats;
use Spatie\Crawler\TransferStatistics;

function createTransferStats(array $handlerStats = []): TransferStats
{
    return new TransferStats(
        new Request('GET', 'https://example.com'),
        new Response(),
        transferTime: $handlerStats['total_time'] ?? 0.0,
        handlerStats: $handlerStats,
    );
}

it('converts transfer time from seconds to milliseconds', function () {
    $stats = TransferStatistics::fromTransferStats(
        createTransferStats(['total_time' => 1.234])
    );

    expect($stats->transferTimeInMs())->toBe(1234.0);
});

it('converts connection time from seconds to milliseconds', function () {
    $stats = TransferStatistics::fromTransferStats(
        createTransferStats(['connect_time' => 0.05])
    );

    expect($stats->connectionTimeInMs())->toBe(50.0);
});

it('converts dns lookup time from seconds to milliseconds', function () {
    $stats = TransferStatistics::fromTransferStats(
        createTransferStats(['namelookup_time' => 0.012])
    );

    expect($stats->dnsLookupTimeInMs())->toBe(12.0);
});

it('converts tls handshake time from seconds to milliseconds', function () {
    $stats = TransferStatistics::fromTransferStats(
        createTransferStats(['appconnect_time' => 0.089])
    );

    expect($stats->tlsHandshakeTimeInMs())->toBe(89.0);
});

it('converts time to first byte from seconds to milliseconds', function () {
    $stats = TransferStatistics::fromTransferStats(
        createTransferStats(['starttransfer_time' => 0.150])
    );

    expect($stats->timeToFirstByteInMs())->toBe(150.0);
});

it('converts redirect time from seconds to milliseconds', function () {
    $stats = TransferStatistics::fromTransferStats(
        createTransferStats(['redirect_time' => 0.025])
    );

    expect($stats->redirectTimeInMs())->toBe(25.0);
});

it('returns null for missing stats', function () {
    $stats = TransferStatistics::fromTransferStats(
        createTransferStats([])
    );

    expect($stats->transferTimeInMs())->toBeNull();
    expect($stats->connectionTimeInMs())->toBeNull();
    expect($stats->dnsLookupTimeInMs())->toBeNull();
    expect($stats->tlsHandshakeTimeInMs())->toBeNull();
    expect($stats->timeToFirstByteInMs())->toBeNull();
    expect($stats->redirectTimeInMs())->toBeNull();
    expect($stats->primaryIp())->toBeNull();
    expect($stats->downloadSpeedInBytesPerSecond())->toBeNull();
    expect($stats->requestSizeInBytes())->toBeNull();
});

it('returns null for zero-valued stats', function () {
    $stats = TransferStatistics::fromTransferStats(
        createTransferStats([
            'total_time' => 0.0,
            'connect_time' => 0.0,
            'appconnect_time' => 0.0,
        ])
    );

    expect($stats->transferTimeInMs())->toBeNull();
    expect($stats->connectionTimeInMs())->toBeNull();
    expect($stats->tlsHandshakeTimeInMs())->toBeNull();
});

it('returns the effective uri', function () {
    $stats = TransferStatistics::fromTransferStats(
        createTransferStats([])
    );

    expect((string) $stats->effectiveUri())->toBe('https://example.com');
});

it('returns the primary ip', function () {
    $stats = TransferStatistics::fromTransferStats(
        createTransferStats(['primary_ip' => '93.184.216.34'])
    );

    expect($stats->primaryIp())->toBe('93.184.216.34');
});

it('returns download speed in bytes per second', function () {
    $stats = TransferStatistics::fromTransferStats(
        createTransferStats(['speed_download' => 125000.0])
    );

    expect($stats->downloadSpeedInBytesPerSecond())->toBe(125000.0);
});

it('returns request size in bytes', function () {
    $stats = TransferStatistics::fromTransferStats(
        createTransferStats(['request_size' => 256])
    );

    expect($stats->requestSizeInBytes())->toBe(256);
});

it('handles precise millisecond rounding', function () {
    $stats = TransferStatistics::fromTransferStats(
        createTransferStats(['total_time' => 0.1236789])
    );

    expect($stats->transferTimeInMs())->toBe(123.679);
});
