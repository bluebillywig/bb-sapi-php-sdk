<?php

namespace BlueBillywig\Entities;

use BlueBillywig\Entity;
use BlueBillywig\Request;
use BlueBillywig\Response;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;

/**
 * Representation of the Channel resource on the Blue Billywig SAPI
 *
 * @method Response list(int $limit = 15, int $offset = 0, string $sort = 'createddate desc') Retrieve a list of Channels. @see listAsync
 * @method Response get(int|string $id) Retrieve a Channel by its ID. @see getAsync
 */
class Channel extends Entity
{
    /**
     * Retrieve a list of Channels and return a promise.
     *
     * @param int $limit Limit the amount of results, defaults to 15.
     * @param int $offset Set the offset of the subset of results, defaults to 0.
     * @param string $sort Sort the results, defaults to 'createddate desc'.
     */
    public function listAsync(
        int $limit = 15,
        int $offset = 0,
        string $sort = 'createddate desc'
    ): PromiseInterface {
        $requestOptions = [
            RequestOptions::QUERY => [
                'limit' => $limit,
                'offset' => $offset,
                'sort' => $sort,
            ],
        ];
        return $this->sdk->sendRequestAsync(
            new Request('GET', '/sapi/channel'),
            $requestOptions
        );
    }

    /**
     * Retrieve a Channel by its ID and return a promise.
     *
     * @param int|string $id The ID of the Channel.
     */
    public function getAsync(int|string $id): PromiseInterface
    {
        return $this->sdk->sendRequestAsync(
            new Request('GET', "/sapi/channel/$id")
        );
    }
}
