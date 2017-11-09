<?php

namespace Spatie\Crawler\Test;

use Throwable;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    public function skipIfTestServerIsNotRunning()
    {
        try {
            file_get_contents('http://localhost:8080');
        } catch (Throwable $e) {
            $this->markTestSkipped('The testserver is not running.');
        }
    }
}
