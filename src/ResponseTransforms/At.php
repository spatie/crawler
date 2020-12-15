<?php

declare(strict_types=1);

namespace Spatie\Crawler\ResponseTransforms;

class At
{
    /**
     * Position used to add a transform at the beginning of the collection.
     *
     * @var int
     */
    const THE_BEGINNING = 1;

    /**
     * Position used to add a transform at the end of the collection.
     *
     * @var int
     */
    const THE_END = 2;
}
