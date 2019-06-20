<?php

namespace Spatie\Crawler;

use Psr\Http\Message\UriInterface;

class CrawlUrl
{
    /** @var \Psr\Http\Message\UriInterface */
    public $url;

    /** @var \Psr\Http\Message\UriInterface */
    public $foundOnUrl;

    /** @var mixed */
    protected $id;

    /** @var int */
    protected $attempts = 0;

    public static function create(UriInterface $url, ?UriInterface $foundOnUrl = null, $id = null)
    {
        $static = new static($url, $foundOnUrl);

        if ($id !== null) {
            $static->setId($id);
        }

        return $static;
    }

    protected function __construct(UriInterface $url, $foundOnUrl = null)
    {
        $this->url = $url;
        $this->foundOnUrl = $foundOnUrl;
    }

    /**
     * @return mixed|null
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Returns the number of attempts to load this URL.
     *
     * @return int
     */
    public function getAttempts() : int
    {
        return $this->attempts;
    }

    /**
     * @return void
     */
    public function incrementAttempts()
    {
        $this->attempts++;
    }
}
