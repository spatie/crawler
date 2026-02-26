<?php

namespace Spatie\Crawler\JavaScriptRenderers;

use Spatie\Browsershot\Browsershot;

class BrowsershotRenderer implements JavaScriptRenderer
{
    protected Browsershot $browsershot;

    public function __construct(?Browsershot $browsershot = null)
    {
        $this->browsershot = $browsershot ?? new Browsershot;
    }

    public function getRenderedHtml(string $url): string
    {
        $html = $this->browsershot->setUrl($url)->bodyHtml();

        return html_entity_decode($html);
    }
}
