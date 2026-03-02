<?php

namespace Spatie\Crawler\CrawlProfiles;

use Closure;

class ClosureCrawlProfile implements CrawlProfile
{
    public function __construct(
        protected Closure $closure,
    ) {}

    public function shouldCrawl(string $url): bool
    {
        return ($this->closure)($url);
    }
}
