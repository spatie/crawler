<?php

namespace Spatie\Crawler\CrawlQueue;

use Spatie\Crawler\CrawlUrl;

/**
 * This interface extends the base CrawlQueue for backwards compatibility with existing CrawlQueue implementations.
 * An application requiring the retry feature (making use of the RetryProfile interface) must use a crawl queue that
 * implements RetryableCrawlQueue. Note that the default crawl queue, CollectionCrawlQueue, already implements this
 * interface.
 *
 * @todo v5: merge this interface with CrawlQueue
 */
interface RetryableCrawlQueue extends CrawlQueue
{
    /**
     * Puts the given URL back in the pending URLs queue.
     * If the URL is not known or is already in the pending queue, this method does nothing.
     *
     * @param CrawlUrl $crawlUrl
     *
     * @return void
     */
    public function retry(CrawlUrl $crawlUrl);
}
