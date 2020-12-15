<?php

declare(strict_types=1);

namespace Spatie\Crawler\ResponseTransforms;

use Spatie\Crawler\Exceptions\InvalidTransformPositionException;

class ResponseTransformCollection
{
    /** @var ResponseTransformContract[] */
    private array $transforms = [];

    /**
     * Push a transform to the collection pipe.
     *
     * @param ResponseTransformContract $transform
     * @param int $position
     * @return $this
     */
    public function push(ResponseTransformContract $transform, int $position): self
    {
        switch ($position) {
            case At::THE_BEGINNING:
                $this->prepend($transform);
                break;
            case At::THE_END:
                $this->append($transform);
                break;
            default:
                throw new InvalidTransformPositionException('Position ' . $position . ' is an invalid value.');
        }

        return $this;
    }

    /**
     * Add a transform to the beginning of the collection.
     *
     * @param ResponseTransformContract $transform
     * @return $this
     */
    protected function prepend(ResponseTransformContract $transform): self
    {
        array_unshift($this->transforms, $transform);

        return $this;
    }

    /**
     * Add a transform to the end of the collection.
     *
     * @param ResponseTransformContract $transform
     * @return $this
     */
    protected function append(ResponseTransformContract $transform): self
    {
        $this->transforms[] = $transform;

        return $this;
    }

    /**
     * Retrieve all transforms.
     *
     * @return ResponseTransformContract[]
     */
    public function all(): array
    {
        return $this->transforms;
    }
}
