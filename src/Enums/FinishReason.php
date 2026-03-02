<?php

namespace Spatie\Crawler\Enums;

enum FinishReason: string
{
    case Completed = 'completed';
    case CrawlLimitReached = 'crawl_limit_reached';
    case TimeLimitReached = 'time_limit_reached';
    case Interrupted = 'interrupted';
}
