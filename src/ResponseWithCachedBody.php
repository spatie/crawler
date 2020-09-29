<?php

namespace Spatie\Crawler;

use GuzzleHttp\Psr7\Response;

class ResponseWithCachedBody extends Response
{
    protected ?string $cachedBody = null;

    public static function fromGuzzlePsr7Response(Response $response)
    {
        return new static(
            $response->getStatusCode(),
            $response->getHeaders(),
            $response->getBody(),
            $response->getProtocolVersion(),
            $response->getReasonPhrase()
        );
    }

    public function setCachedBody(?string $body = null)
    {
        $this->cachedBody = $body;
    }

    public function getCachedBody(): ?string
    {
        return $this->cachedBody;
    }
}
