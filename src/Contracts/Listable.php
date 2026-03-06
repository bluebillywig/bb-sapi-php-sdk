<?php

namespace BlueBillywig\Contracts;

use GuzzleHttp\Promise\PromiseInterface;

/**
 * Indicates that the entity supports listing resources.
 *
 * @method \BlueBillywig\Response list(int $limit = 15, int $offset = 0, string $sort = 'createddate desc') Retrieve a list of resources. @see listAsync
 */
interface Listable
{
    /**
     * Retrieve a list of resources and return a promise.
     *
     * @param int $limit Limit the amount of results, defaults to 15.
     * @param int $offset Set the offset of the subset of results, defaults to 0.
     * @param string $sort Sort the results, defaults to 'createddate desc'.
     */
    public function listAsync(int $limit = 15, int $offset = 0, string $sort = 'createddate desc'): PromiseInterface;
}
