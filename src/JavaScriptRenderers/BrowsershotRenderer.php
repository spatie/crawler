<?php

namespace Spatie\Crawler\JavaScriptRenderers;

use Spatie\Browsershot\Browsershot;

class BrowsershotRenderer implements JavaScriptRenderer
{
    public function __construct(
        protected Browsershot $browsershot = new Browsershot,
    ) {}

    public function getRenderedHtml(string $url): string
    {
        $html = $this->browsershot->setUrl($url)->bodyHtml();

        return html_entity_decode($html);
    }
}
