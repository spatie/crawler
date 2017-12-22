<?php

namespace Spatie\Crawler;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class CrawlUrl
{
    /** @var \Psr\Http\Message\UriInterface */
    public $url;

    /** @var \Psr\Http\Message\UriInterface */
    public $foundOnUrl;

    /** @var int */
    protected $id;

    public static function create($url, $foundOnUrl = null, int $id = null)
    {
        if (! $url instanceof UriInterface) {
            $url = new Uri($url);
        }

        if ($url->getScheme() === '') {
            $url = $url->withScheme('http');
        }

        if ($foundOnUrl !== null && ! $foundOnUrl instanceof UriInterface) {
            $foundOnUrl = new Uri($foundOnUrl);
        }

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

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id)
    {
        $this->id = $id;
    }
}
