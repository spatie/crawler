<?php

namespace Spatie\Crawler\Concerns;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Spatie\Crawler\Faking\FakeHandler;

trait ConfiguresRequests
{
    protected array $extraHeaders = [];

    protected ?int $connectTimeout = null;

    protected ?int $requestTimeout = null;

    protected ?string $basicAuthUsername = null;

    protected ?string $basicAuthPassword = null;

    protected ?string $authToken = null;

    protected string $authTokenType = 'Bearer';

    protected ?bool $verifySsl = null;

    protected ?string $proxy = null;

    protected ?array $cookies = null;

    protected ?string $cookieDomain = null;

    protected array $queryParameters = [];

    protected ?string $userAgent = null;

    protected array $clientOptions;

    protected ?Client $client = null;

    /** @var array<array{callable, string}> */
    protected array $middlewares = [];

    protected int $retryTimes = 0;

    protected int $retryDelayMs = 500;

    protected static array $defaultClientOptions = [
        RequestOptions::COOKIES => true,
        RequestOptions::CONNECT_TIMEOUT => 10,
        RequestOptions::TIMEOUT => 10,
        RequestOptions::ALLOW_REDIRECTS => false,
        RequestOptions::HEADERS => [
            'User-Agent' => self::DEFAULT_USER_AGENT,
        ],
    ];

    public function userAgent(string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function headers(array $headers): self
    {
        $this->extraHeaders = array_merge($this->extraHeaders, $headers);

        return $this;
    }

    public function connectTimeout(int $connectTimeoutInSeconds): self
    {
        $this->connectTimeout = $connectTimeoutInSeconds;

        return $this;
    }

    public function requestTimeout(int $requestTimeoutInSeconds): self
    {
        $this->requestTimeout = $requestTimeoutInSeconds;

        return $this;
    }

    public function basicAuth(string $username, string $password): self
    {
        $this->authToken = null;
        $this->basicAuthUsername = $username;
        $this->basicAuthPassword = $password;

        return $this;
    }

    public function token(string $token, string $type = 'Bearer'): self
    {
        $this->basicAuthUsername = null;
        $this->basicAuthPassword = null;
        $this->authToken = $token;
        $this->authTokenType = $type;

        return $this;
    }

    public function withoutVerifying(): self
    {
        $this->verifySsl = false;

        return $this;
    }

    public function proxy(string $proxy): self
    {
        $this->proxy = $proxy;

        return $this;
    }

    public function cookies(array $cookies, string $domain): self
    {
        $this->cookies = $cookies;
        $this->cookieDomain = $domain;

        return $this;
    }

    public function queryParameters(array $parameters): self
    {
        $this->queryParameters = array_merge($this->queryParameters, $parameters);

        return $this;
    }

    public function middleware(callable $middleware, string $name = ''): self
    {
        $this->middlewares[] = [$middleware, $name];

        return $this;
    }

    public function retry(int $times = 2, int $delayInMs = 500): self
    {
        $this->retryTimes = $times;
        $this->retryDelayMs = $delayInMs;

        return $this;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent ?? static::DEFAULT_USER_AGENT;
    }

    protected function buildClient(): Client
    {
        $options = $this->clientOptions;

        $options[RequestOptions::HEADERS] = $options[RequestOptions::HEADERS] ?? [];

        if ($this->userAgent !== null) {
            $options[RequestOptions::HEADERS]['User-Agent'] = $this->userAgent;
        }

        if (! empty($this->extraHeaders)) {
            $options[RequestOptions::HEADERS] = array_merge(
                $options[RequestOptions::HEADERS],
                $this->extraHeaders,
            );
        }

        if ($this->connectTimeout !== null) {
            $options[RequestOptions::CONNECT_TIMEOUT] = $this->connectTimeout;
        }

        if ($this->requestTimeout !== null) {
            $options[RequestOptions::TIMEOUT] = $this->requestTimeout;
        }

        if ($this->basicAuthUsername !== null) {
            $options[RequestOptions::AUTH] = [
                $this->basicAuthUsername,
                $this->basicAuthPassword,
            ];
        }

        if ($this->authToken !== null) {
            $options[RequestOptions::HEADERS] = $options[RequestOptions::HEADERS] ?? [];
            $options[RequestOptions::HEADERS]['Authorization'] = trim($this->authTokenType.' '.$this->authToken);
        }

        if ($this->verifySsl === false) {
            $options[RequestOptions::VERIFY] = false;
        }

        if ($this->proxy !== null) {
            $options[RequestOptions::PROXY] = $this->proxy;
        }

        if ($this->cookies !== null) {
            $cookieJar = CookieJar::fromArray(
                $this->cookies,
                $this->cookieDomain,
            );
            $options[RequestOptions::COOKIES] = $cookieJar;
        }

        if (! empty($this->queryParameters)) {
            $options[RequestOptions::QUERY] = $this->queryParameters;
        }

        $throttle = $this->throttle;
        $options[RequestOptions::ON_STATS] = function (TransferStats $stats) use ($throttle) {
            $this->setTransferStats((string) $stats->getEffectiveUri(), $stats);

            if ($throttle !== null) {
                $throttle->recordResponseTime($stats->getTransferTime());
            }
        };

        if ($this->fakes !== null) {
            $stack = HandlerStack::create(new FakeHandler($this->fakes));
            $options[RequestOptions::HTTP_ERRORS] = false;
        } else {
            $existingHandler = $options['handler'] ?? null;

            $stack = $existingHandler instanceof HandlerStack
                ? $existingHandler
                : HandlerStack::create($existingHandler);
        }

        if ($this->retryTimes > 0) {
            $stack->push($this->createRetryMiddleware(), 'retry');
        }

        foreach ($this->middlewares as [$middleware, $name]) {
            $stack->push($middleware, $name);
        }

        $options['handler'] = $stack;

        $this->client = new Client($options);

        return $this->client;
    }

    protected function createRetryMiddleware(): callable
    {
        $delayMs = $this->retryDelayMs;
        $maxRetries = $this->retryTimes;

        return Middleware::retry(
            function (int $retries, RequestInterface $request, ?ResponseInterface $response = null, ?Exception $exception = null) use ($maxRetries): bool {
                if ($retries >= $maxRetries) {
                    return false;
                }

                if ($exception instanceof ConnectException) {
                    return true;
                }

                if ($response !== null && $response->getStatusCode() >= 500) {
                    return true;
                }

                return false;
            },
            function (int $retries) use ($delayMs): int {
                return $delayMs * ($retries + 1);
            },
        );
    }
}
