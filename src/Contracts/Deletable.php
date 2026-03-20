<?php

namespace BlueBillywig\Contracts;

use GuzzleHttp\Promise\PromiseInterface;

/**
 * Indicates that the entity supports deleting resources.
 *
 * @method \BlueBillywig\Response delete(int|string $id) Delete a resource by its ID. @see deleteAsync
 */
interface Deletable
{
    /**
     * Delete a resource by its ID and return a promise.
     *
     * @param int|string $id The ID of the resource.
     */
    public function deleteAsync(int|string $id): PromiseInterface;
}
