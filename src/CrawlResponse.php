<?php

namespace Spatie\Crawler;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\ResponseInterface;
use Spatie\Crawler\Enums\ResourceType;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

class CrawlResponse
{
    protected ?string $cachedBody = null;

    protected ?DomCrawler $dom = null;

    protected ?TransferStatistics $cachedTransferStatistics = null;

    public function __construct(
        protected ResponseInterface $response,
        protected ?string $foundOnUrl = null,
        protected ?string $linkText = null,
        protected int $depth = 0,
        protected ResourceType $resourceType = ResourceType::Link,
        protected ?TransferStats $transferStats = null,
    ) {}

    public static function fake(
        string $body = '',
        int $status = 200,
        array $headers = [],
        ResourceType $resourceType = ResourceType::Link,
    ): static {
        $response = new Response($status, $headers, $body);

        return new static($response, resourceType: $resourceType);
    }

    public function status(): int
    {
        return $this->response->getStatusCode();
    }

    public function body(): string
    {
        return $this->cachedBody ??= (string) $this->response->getBody();
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
        return $this->dom ??= new DomCrawler($this->body());
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

    public function resourceType(): ResourceType
    {
        return $this->resourceType;
    }

    public function transferStats(): ?TransferStatistics
    {
        if ($this->transferStats === null) {
            return null;
        }

        return $this->cachedTransferStatistics ??= TransferStatistics::fromTransferStats($this->transferStats);
    }

    public function toPsrResponse(): ResponseInterface
    {
        return $this->response;
    }
}
