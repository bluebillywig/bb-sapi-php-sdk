<?php

namespace BlueBillywig\Entities;

use BlueBillywig\Entity;
use BlueBillywig\Request;
use BlueBillywig\Response;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Representation of the MediaClipList resource on the Blue Billywig SAPI
 *
 * @method Response get(int|string $id) Retrieve a MediaClipList by its ID. @see getAsync
 */
class MediaClipList extends Entity
{
    /**
     * Retrieve a MediaClipList by its ID and return a promise.
     *
     * @param int|string $id The ID of the MediaClipList.
     */
    public function getAsync(int|string $id): PromiseInterface
    {
        return $this->sdk->sendRequestAsync(new Request(
            "GET",
            "/sapi/cliplist/$id"
        ));
    }
}
