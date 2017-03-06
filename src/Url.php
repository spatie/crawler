<?php

namespace Spatie\Crawler;

class Url
{
    /** @var null|string */
    public $scheme;

    /** @var null|string */
    public $host;

    /** @var int */
    public $port = 80;

    /** @var null|string */
    public $path;

    /** @var null|string */
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

    public function isRelative(): bool
    {
        return is_null($this->host);
    }

    public function isProtocolIndependent(): bool
    {
        return is_null($this->scheme);
    }

    public function hasCrawlableScheme(): bool
    {
        return in_array($this->scheme, [null, 'http', 'https']);
    }

    /**
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
     * @return $this
     */
    public function removeFragment()
    {
        $this->path = explode('#', $this->path)[0];

        return $this;
    }

    /**
     * @param $path
     *
     * @return $this
     */
    public function setPath(string $path)
    {
        $this->path = $path;

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
     * @deprecated This function is not being used internally anymore and will be removed in the next major version.
     *
     * @return null|string
     */
    public function directory()
    {
        $segments = $this->segments();
        array_pop($segments);

        return implode('/', $segments).'/';
    }

    /**
     * @deprecated This function is not being used internally anymore and will be removed in the next major version.
     *
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
     * @return string
     */
    public function __toString()
    {
        $path = $this->startsWith($this->path, '/') ? substr($this->path, 1) : $this->path;

        $port = ($this->port === 80 ? '' : ":{$this->port}");

        $queryString = (is_null($this->query) ? '' : "?{$this->query}");

        return "{$this->scheme}://{$this->host}{$port}/{$path}{$queryString}";
    }

    /**
     * @param string|null $haystack
     * @param string|array $needles
     *
     * @return bool
     */
    public function startsWith($haystack, $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && substr($haystack, 0, strlen($needle)) === (string) $needle) {
                return true;
            }
        }

        return false;
    }
}
