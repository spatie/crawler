<?php

namespace Spatie\Crawler\Test;

use Throwable;
use PHPUnit_Framework_TestCase;

class TestCase extends PHPUnit_Framework_TestCase
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
