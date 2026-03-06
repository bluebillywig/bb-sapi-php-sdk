<?php

namespace BlueBillywig\Contracts;

use GuzzleHttp\Promise\PromiseInterface;

/**
 * Indicates that the entity supports creating new resources.
 *
 * @method \BlueBillywig\Response create(array $props) Create a resource. @see createAsync
 */
interface Creatable
{
    /**
     * Create a resource and return a promise.
     *
     * @param array $props The properties of the resource to create.
     */
    public function createAsync(array $props): PromiseInterface;
}
