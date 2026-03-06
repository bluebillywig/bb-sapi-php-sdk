<?php

namespace BlueBillywig\Entities;

use BlueBillywig\Contracts\Creatable;
use BlueBillywig\Contracts\Deletable;
use BlueBillywig\Contracts\Gettable;
use BlueBillywig\Contracts\Listable;
use BlueBillywig\Contracts\Updatable;
use BlueBillywig\Entity;
use BlueBillywig\Request;
use BlueBillywig\Response;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;

/**
 * Representation of the Subtitle resource on the Blue Billywig SAPI.
 *
 * @method Response list(int $limit = 15, int $offset = 0, string $sort = 'createddate desc') Retrieve a list of Subtitles. @see listAsync
 * @method Response get(int|string $id) Retrieve a Subtitle by its ID. @see getAsync
 * @method Response create(array $props) Create a Subtitle. @see createAsync
 * @method Response update(int|string $id, array $props) Update a Subtitle by its ID. @see updateAsync
 * @method Response delete(int|string $id) Delete a Subtitle by its ID. @see deleteAsync
 */
class Subtitle extends Entity implements Listable, Gettable, Creatable, Updatable, Deletable
{
    /**
     * Retrieve a list of Subtitles and return a promise.
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
            new Request('GET', '/sapi/subtitle'),
            $requestOptions
        );
    }

    /**
     * Retrieve a Subtitle by its ID and return a promise.
     *
     * @param int|string $id The ID of the Subtitle.
     */
    public function getAsync(int|string $id): PromiseInterface
    {
        return $this->sdk->sendRequestAsync(
            new Request('GET', "/sapi/subtitle/$id")
        );
    }

    /**
     * Create a Subtitle and return a promise.
     *
     * @param array{
     *     status?: string,
     *     mediaclipId?: int,
     *     languageId?: int,
     *     isocode?: string,
     *     originalfilename?: string,
     *     uploadIdentifier?: string
     * } $props The properties of the Subtitle to create.
     */
    public function createAsync(array $props): PromiseInterface
    {
        return $this->sdk->sendRequestAsync(new Request(
            "PUT",
            "/sapi/subtitle"
        ), [
            RequestOptions::JSON => $props
        ]);
    }

    /**
     * Update a Subtitle by its ID and return a promise.
     *
     * @param int|string $id The ID of the Subtitle.
     * @param array{
     *     status?: string,
     *     mediaclipId?: int,
     *     languageId?: int,
     *     isocode?: string,
     *     originalfilename?: string,
     *     uploadIdentifier?: string
     * } $props The properties of the Subtitle to update.
     */
    public function updateAsync(int|string $id, array $props): PromiseInterface
    {
        return $this->sdk->sendRequestAsync(new Request(
            "PUT",
            "/sapi/subtitle/$id"
        ), [
            RequestOptions::JSON => $props
        ]);
    }

    /**
     * Delete a Subtitle by its ID and return a promise.
     *
     * @param int|string $id The ID of the Subtitle.
     *
     * @throws \BlueBillywig\Exception\HTTPRequestException
     */
    public function deleteAsync(int|string $id): PromiseInterface
    {
        return $this->sdk->sendRequestAsync(
            new Request('DELETE', "/sapi/subtitle/$id")
        );
    }
}
