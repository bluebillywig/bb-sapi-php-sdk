<?php

namespace BlueBillywig\Contracts;

use GuzzleHttp\Promise\PromiseInterface;

/**
 * Indicates that the entity supports updating existing resources.
 *
 * @method \BlueBillywig\Response update(int|string $id, array $props) Update a resource by its ID. @see updateAsync
 */
interface Updatable
{
    /**
     * Update a resource by its ID and return a promise.
     *
     * @param int|string $id The ID of the resource.
     * @param array $props The properties of the resource to update.
     */
    public function updateAsync(int|string $id, array $props): PromiseInterface;
}
