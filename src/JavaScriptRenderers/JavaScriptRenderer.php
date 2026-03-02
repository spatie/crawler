<?php

namespace Spatie\Crawler\JavaScriptRenderers;

interface JavaScriptRenderer
{
    public function getRenderedHtml(string $url): string;
}
