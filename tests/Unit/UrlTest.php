<?php

namespace Spatie\Crawler\Test;

use PHPUnit_Framework_TestCase;
use Spatie\Crawler\Url;

class UrlTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Url
     */
    protected $testUrl;

    public function setUp()
    {
        parent::setUp();

        $this->testUrl = new Url('https://spatie.be/opensource');
    }

    /**
     * @test
     */
    public function it_can_parse_an_url()
    {
        $this->assertEquals('https', $this->testUrl->scheme);
        $this->assertEquals('spatie.be', $this->testUrl->host);
        $this->assertEquals('/opensource', $this->testUrl->path);
    }

    /**
     * @test
     */
    public function it_can_be_converted_to_a_string()
    {
        $this->assertEquals('https://spatie.be/opensource', (string) $this->testUrl);
    }

    /**
     * @test
     */
    public function it_can_determine_if_an_url_is_relative()
    {
        $url = new Url('/opensource');

        $this->assertTrue($url->isRelative());

        $url = new Url($this->testUrl);

        $this->assertFalse($url->isRelative());
    }

    /**
     * @test
     */
    public function it_can_determine_if_an_url_is_protocol_independent()
    {
        $url = new Url('//google.com/test');

        $this->assertTrue($url->isProtocolIndependent());

        $url = new Url($this->testUrl);

        $this->assertFalse($url->isProtocolIndependent());
    }

    /**
     * @test
     */
    public function it_can_change_the_host()
    {
        $this->testUrl->setHost('google.com');

        $this->assertEquals('https://google.com/opensource', (string) $this->testUrl);
    }

    /**
     * @test
     */
    public function it_can_change_the_scheme()
    {
        $this->testUrl->setScheme('http');

        $this->assertEquals('http://spatie.be/opensource', (string) $this->testUrl);
    }

    /**
     * @test
     */
    public function it_has_factory_method_to_create_an_instance_from_a_string()
    {
        $url = 'https://spatie.be/opensource';

        $this->assertEquals($url, (string) Url::create($url));
    }

    /**
     * @test
     */
    public function it_can_remove_the_fragment()
    {
        $url = Url::create('https://spatie.be/team#willem')->removeFragment();

        $this->assertEquals('https://spatie.be/team', (string) $url);
    }

    /**
     * @test
     */
    public function it_can_determine_if_the_url_is_an_email_url()
    {
        $this->assertFalse(Url::create('https://spatie.be/')->isEmailUrl());
        $this->assertTrue(Url::create('mailto:info@spatie.be')->isEmailUrl());
    }
}
