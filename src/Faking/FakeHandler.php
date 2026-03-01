<?php

namespace Spatie\Crawler\Faking;

use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Spatie\Crawler\CrawlResponse;

class FakeHandler
{
    /** @var array<string, ResponseInterface> */
    protected array $responses = [];

    /** @param array<string, string|CrawlResponse> $fakes */
    public function __construct(array $fakes)
    {
        foreach ($fakes as $url => $content) {
            $normalizedUrl = $this->normalizeUrl($url);

            if ($content instanceof CrawlResponse) {
                $this->responses[$normalizedUrl] = $content->toPsrResponse();
            } else {
                $this->responses[$normalizedUrl] = new Response(200, ['Content-Type' => 'text/html'], $content);
            }
        }
    }

    public function __invoke(RequestInterface $request, array $options = []): FulfilledPromise
    {
        $url = (string) $request->getUri();
        $normalizedUrl = $this->normalizeUrl($url);

        if (isset($this->responses[$normalizedUrl])) {
            return new FulfilledPromise($this->responses[$normalizedUrl]);
        }

        // Try without trailing slash
        $withoutSlash = rtrim($normalizedUrl, '/');
        if (isset($this->responses[$withoutSlash])) {
            return new FulfilledPromise($this->responses[$withoutSlash]);
        }

        // Try with trailing slash
        $withSlash = $withoutSlash.'/';
        if (isset($this->responses[$withSlash])) {
            return new FulfilledPromise($this->responses[$withSlash]);
        }

        // Handle robots.txt requests: return 404 if not explicitly faked
        if (str_ends_with($normalizedUrl, '/robots.txt')) {
            return new FulfilledPromise(new Response(404));
        }

        return new FulfilledPromise(new Response(404, [], 'Not Found'));
    }

    protected function normalizeUrl(string $url): string
    {
        return explode('#', $url, 2)[0];
    }
}
