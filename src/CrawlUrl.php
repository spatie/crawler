<?php

namespace Spatie\Crawler;

use Psr\Http\Message\UriInterface;

class CrawlUrl
{
    public UriInterface $url;

    public ?UriInterface $foundOnUrl = null;

    /** @var mixed */
    protected $id;

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
}
