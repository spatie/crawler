<?php

namespace Spatie\Crawler;

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
     * @param string $url
     *
     * @return static
     */
    public static function create(string $url)
    {
        return new static($url);
    }

    public function __construct(string $url)
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
     */
    public function isRelative(): bool
    {
        return is_null($this->host);
    }

    /**
     * Determine if the url is protocol independent.
     */
    public function isProtocolIndependent(): bool
    {
        return is_null($this->scheme);
    }

    /**
     * Determine if this is a mailto-link.
     */
    public function isEmailUrl(): bool
    {
        return $this->scheme === 'mailto';
    }

    /**
     * Determine if this is a tel-link.
     */
    public function isTelUrl(): bool
    {
        return $this->scheme === 'tel';
    }

    /**
     * Determine if this is an inline javascript.
     */
    public function isJavascript(): bool
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
    public function setScheme(string $scheme)
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
    public function setHost(string $host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @param $port
     *
     * @return $this
     */
    public function setPort(int $port)
    {
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
    public function segments(int $index = null)
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
    public function segment(int $index)
    {
        if (! isset($this->segments()[$index - 1])) {
            return;
        }

        return $this->segments()[$index - 1];
    }

    public function isEqual(Url $otherUrl): bool
    {
        return (string) $this === (string) $otherUrl;
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
