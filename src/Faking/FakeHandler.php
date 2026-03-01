<?php

namespace Spatie\Crawler\Faking;

use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RedirectMiddleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Spatie\Crawler\CrawlResponse;

class FakeHandler
{
    protected const MAX_REDIRECTS = 5;

    /** @var array<string, ResponseInterface> */
    protected array $responses = [];

    /** @param array<string, string|CrawlResponse> $fakes */
    public function __construct(array $fakes)
    {
        foreach ($fakes as $url => $content) {
            $normalizedUrl = $this->normalizeUrl($url);

            $this->responses[$normalizedUrl] = $content instanceof CrawlResponse
                ? $content->toPsrResponse()
                : new Response(200, ['Content-Type' => 'text/html'], $content);
        }
    }

    public function __invoke(RequestInterface $request, array $options = []): FulfilledPromise
    {
        $response = $this->findResponse((string) $request->getUri());

        $redirectHistory = [];

        for ($i = 0; $i < self::MAX_REDIRECTS; $i++) {
            $statusCode = $response->getStatusCode();

            if ($statusCode < 300 || $statusCode >= 400) {
                break;
            }

            $location = $response->getHeaderLine('Location');

            if ($location === '') {
                break;
            }

            $redirectHistory[] = $location;
            $response = $this->findResponse($location);
        }

        if ($redirectHistory !== []) {
            $response = $response->withHeader(RedirectMiddleware::HISTORY_HEADER, $redirectHistory);
        }

        return new FulfilledPromise($response);
    }

    protected function findResponse(string $url): ResponseInterface
    {
        $normalizedUrl = $this->normalizeUrl($url);

        if (isset($this->responses[$normalizedUrl])) {
            return $this->responses[$normalizedUrl];
        }

        // Try without trailing slash
        $withoutSlash = rtrim($normalizedUrl, '/');
        if (isset($this->responses[$withoutSlash])) {
            return $this->responses[$withoutSlash];
        }

        // Try with trailing slash
        $withSlash = $withoutSlash.'/';
        if (isset($this->responses[$withSlash])) {
            return $this->responses[$withSlash];
        }

        // Handle robots.txt requests: return 404 if not explicitly faked
        if (str_ends_with($normalizedUrl, '/robots.txt')) {
            return new Response(404);
        }

        return new Response(404, [], 'Not Found');
    }

    protected function normalizeUrl(string $url): string
    {
        return explode('#', $url, 2)[0];
    }
}
