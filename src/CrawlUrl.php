<?php

namespace Spatie\Crawler;

class CrawlUrl
{
    /** @var \Spatie\Crawler\Url */
    public $url;

    /** @var \Spatie\Crawler\Url */
    public $foundOnUrl;

    /** @var int */
    protected $id;

    public static function create(Url $url, Url $foundOnUrl = null, int $id = null)
    {
        $static = new static($url, $foundOnUrl);
        if ($id !== null) {
            $static->setId($id);
        }

        return $static;
    }

    protected function __construct(Url $url, Url $foundOnUrl = null)
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
