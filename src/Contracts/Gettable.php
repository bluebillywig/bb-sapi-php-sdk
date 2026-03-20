<?php

namespace BlueBillywig\Contracts;

use GuzzleHttp\Promise\PromiseInterface;

/**
 * Indicates that the entity supports retrieving a single resource by ID.
 *
 * @method \BlueBillywig\Response get(int|string $id) Retrieve a resource by its ID. @see getAsync
 */
interface Gettable
{
    /**
     * Retrieve a resource by its ID and return a promise.
     *
     * @param int|string $id The ID of the resource.
     */
    public function getAsync(int|string $id): PromiseInterface;
}
