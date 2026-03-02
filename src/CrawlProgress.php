<?php

namespace Spatie\Crawler;

class CrawlProgress
{
    public int $urlsProcessed {
        get => $this->urlsCrawled + $this->urlsFailed;
    }

    public function __construct(
        public int $urlsCrawled,
        public int $urlsFailed,
        public int $urlsFound,
        public int $urlsPending,
    ) {}
}
