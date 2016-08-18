<?php

namespace Spatie\Crawler;

use Spatie\Crawler\Exceptions\InvalidPortNumber;

class Url
{
    /**
     * @var null|string
     */
    public $scheme;

    /**
     * @var null|string
     */
    public $host;

    /**
     * @var int
     */
    public $port = 80;

    /**
     * @var null|string
     */
    public $path;

    /**
     * @var null|string
     */
    public $query;

    /**
     * @param $url
     *
     * @return static
     */
    public static function create($url)
    {
        return new static($url);
    }

    /**
     * Url constructor.
     *
     * @param $url
     */
    public function __construct($url)
    {
        $urlProperties = parse_url($url);

        foreach (['scheme', 'host', 'path', 'port', 'query'] as $property) {
            if (isset($urlProperties[$property])) {
                $this->$property = $urlProperties[$property];
            }
        }
    }

    /**
     * Determine if the url is relative.
     *
     * @return bool
     */
    public function isRelative()
    {
        return is_null($this->host);
    }

    /**
     * Determine if the url is protocol independent.
     *
     * @return bool
     */
    public function isProtocolIndependent()
    {
        return is_null($this->scheme);
    }

    /**
     * Determine if this is a mailto-link.
     *
     * @return bool
     */
    public function isEmailUrl()
    {
        return $this->scheme === 'mailto';
    }

    /**
     * Determine if this is an inline javascript.
     *
     * @return bool
     */
    public function isJavascript()
    {
        return $this->scheme === 'javascript';
    }

    /**
     * Set the scheme.
     *
     * @param string $scheme
     *
     * @return $this
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;

        return $this;
    }

    /**
     * Set the host.
     *
     * @param string $host
     *
     * @return $this
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @param int $port
     *
     * @return $this
     *
     * @throws \Spatie\Crawler\Exceptions\InvalidPortNumber
     */
    public function setPort($port)
    {
        if (!is_numeric($port)) {
            throw new InvalidPortNumber();
        }

        $this->port = $port;

        return $this;
    }

    /**
     * Remove the fragment.
     *
     * @return $this
     */
    public function removeFragment()
    {
        $this->path = explode('#', $this->path)[0];

        return $this;
    }

    /**
     * @return null|string
     */
    public function path()
    {
        return $this->path;
    }

    /**
     * @param int|null $index
     *
     * @return array|null|string
     */
    public function segments($index = null)
    {
        $segments = collect(explode('/', $this->path()))
            ->filter(function ($value) {
                return $value !== '';
            })
            ->values()
            ->toArray();

        if (! is_null($index)) {
            return $this->segment($index);
        }

        return $segments;
    }

    /**
     * @param int $index
     *
     * @return string|null
     */
    public function segment($index)
    {
        if (! isset($this->segments()[$index - 1])) {
            return null;
        }

        return $this->segments()[$index - 1];
    }

    /**
     * Convert the url to string.
     *
     * @return string
     */
    public function __toString()
    {
        $path = starts_with($this->path, '/') ? substr($this->path, 1) : $this->path;

        $port = ($this->port === 80 ? '' : ":{$this->port}");

        $queryString = (is_null($this->query) ? '' : "?{$this->query}");

        return "{$this->scheme}://{$this->host}{$port}/{$path}{$queryString}";
    }
}
