<?php

namespace Spatie\Crawler\JavaScriptRenderers;

use GuzzleHttp\Client;

class CloudflareRenderer implements JavaScriptRenderer
{
    public function __construct(
        protected string $endpoint,
        protected Client $client = new Client,
    ) {}

    public function getRenderedHtml(string $url): string
    {
        $response = $this->client->post($this->endpoint, [
            'json' => ['url' => $url],
        ]);

        $data = json_decode((string) $response->getBody(), true);

        return $data['content'] ?? '';
    }
}
