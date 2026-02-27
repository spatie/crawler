<?php

namespace Spatie\Crawler\Test\TestServer;

use RuntimeException;

class TestServer
{
    /** @var resource|null */
    private static $process = null;

    private static int $port = 0;

    /** @var array<int, resource> */
    private static array $pipes = [];

    public static function start(): void
    {
        if (self::$process !== null) {
            return;
        }

        self::$port = self::findFreePort();

        $command = sprintf(
            'php -S 127.0.0.1:%d %s',
            self::$port,
            escapeshellarg(__DIR__.'/server.php'),
        );

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        self::$process = proc_open($command, $descriptors, self::$pipes);

        if (! is_resource(self::$process)) {
            throw new RuntimeException('Failed to start test server');
        }

        self::waitForServer();
    }

    public static function stop(): void
    {
        if (self::$process === null) {
            return;
        }

        foreach (self::$pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        proc_terminate(self::$process);
        proc_close(self::$process);

        self::$process = null;
        self::$pipes = [];
    }

    public static function baseUrl(): string
    {
        return sprintf('http://127.0.0.1:%d', self::$port);
    }

    private static function findFreePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $address = stream_socket_get_name($socket, false);
        fclose($socket);

        return (int) substr($address, strrpos($address, ':') + 1);
    }

    private static function waitForServer(): void
    {
        $maxAttempts = 50;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $context = stream_context_create(['http' => ['timeout' => 1]]);
            $result = @file_get_contents(self::baseUrl().'/robots.txt', false, $context);

            if ($result !== false) {
                return;
            }

            usleep(100_000);
        }

        throw new RuntimeException('Test server failed to start within timeout');
    }
}
