<?php

namespace Spatie\Crawler;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

class CrawlResponse
{
    protected ?string $cachedBody = null;

    protected ?DomCrawler $dom = null;

    protected ?string $foundOnUrl;

    protected ?string $linkText;

    protected int $depth;

    public function __construct(
        protected ResponseInterface $response,
        ?string $foundOnUrl = null,
        ?string $linkText = null,
        int $depth = 0,
    ) {
        $this->foundOnUrl = $foundOnUrl;
        $this->linkText = $linkText;
        $this->depth = $depth;
    }

    public static function fake(
        string $body = '',
        int $status = 200,
        array $headers = [],
    ): static {
        $response = new Response($status, $headers, $body);

        return new static($response);
    }

    public function status(): int
    {
        return $this->response->getStatusCode();
    }

    public function body(): string
    {
        if ($this->cachedBody === null) {
            $this->cachedBody = (string) $this->response->getBody();
        }

        return $this->cachedBody;
    }

    public function setCachedBody(string $body): void
    {
        $this->cachedBody = $body;
    }

    public function header(string $name): ?string
    {
        $value = $this->response->getHeaderLine($name);

        return $value !== '' ? $value : null;
    }

    public function headers(): array
    {
        return $this->response->getHeaders();
    }

    public function dom(): DomCrawler
    {
        if ($this->dom === null) {
            $this->dom = new DomCrawler($this->body());
        }

        return $this->dom;
    }

    public function isSuccessful(): bool
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    public function isRedirect(): bool
    {
        return $this->status() >= 300 && $this->status() < 400;
    }

    public function foundOnUrl(): ?string
    {
        return $this->foundOnUrl;
    }

    public function linkText(): ?string
    {
        return $this->linkText;
    }

    public function depth(): int
    {
        return $this->depth;
    }

    public function toPsrResponse(): ResponseInterface
    {
        return $this->response;
    }
}
