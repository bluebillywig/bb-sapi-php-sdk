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
 * Representation of the Playlist resource on the Blue Billywig SAPI.
 *
 * @method Response list(int $limit = 15, int $offset = 0, string $sort = 'createddate desc') Retrieve a list of Playlists. @see listAsync
 * @method Response get(int|string $id) Retrieve a Playlist by its ID. @see getAsync
 * @method Response create(array $props) Create a Playlist. @see createAsync
 * @method Response update(int|string $id, array $props) Update a Playlist by its ID. @see updateAsync
 * @method Response delete(int|string $id) Delete a Playlist by its ID. @see deleteAsync
 */
class Playlist extends Entity implements Listable, Gettable, Creatable, Updatable, Deletable
{
    /**
     * Retrieve a list of Playlists and return a promise.
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
            new Request('GET', '/sapi/playlist'),
            $requestOptions
        );
    }

    /**
     * Retrieve a Playlist by its ID and return a promise.
     *
     * @param int|string $id The ID of the Playlist.
     */
    public function getAsync(int|string $id): PromiseInterface
    {
        return $this->sdk->sendRequestAsync(
            new Request('GET', "/sapi/playlist/$id")
        );
    }

    /**
     * Create a Playlist and return a promise.
     *
     * @param array{
     *     title?: string,
     *     description?: string,
     *     status?: string,
     *     type?: string,
     *     query?: string,
     *     mediatype?: string,
     *     usetype?: string,
     *     copyright?: string,
     *     author?: string,
     *     deeplinkUrl?: string,
     *     shortTitle?: string,
     *     externalUrl?: string,
     *     shuffleOrder?: bool,
     *     useSuggest?: bool,
     *     extraSuggestQuery?: string,
     *     allowDatasource?: bool,
     *     limit?: int,
     *     sort?: string
     * } $props The properties of the Playlist to create.
     */
    public function createAsync(array $props): PromiseInterface
    {
        return $this->sdk->sendRequestAsync(new Request(
            "PUT",
            "/sapi/playlist"
        ), [
            RequestOptions::JSON => $props
        ]);
    }

    /**
     * Update a Playlist by its ID and return a promise.
     *
     * @param int|string $id The ID of the Playlist.
     * @param array{
     *     title?: string,
     *     description?: string,
     *     status?: string,
     *     type?: string,
     *     query?: string,
     *     mediatype?: string,
     *     usetype?: string,
     *     copyright?: string,
     *     author?: string,
     *     deeplinkUrl?: string,
     *     shortTitle?: string,
     *     externalUrl?: string,
     *     shuffleOrder?: bool,
     *     useSuggest?: bool,
     *     extraSuggestQuery?: string,
     *     allowDatasource?: bool,
     *     limit?: int,
     *     sort?: string
     * } $props The properties of the Playlist to update.
     */
    public function updateAsync(int|string $id, array $props): PromiseInterface
    {
        return $this->sdk->sendRequestAsync(new Request(
            "PUT",
            "/sapi/playlist/$id"
        ), [
            RequestOptions::JSON => $props
        ]);
    }

    /**
     * Delete a Playlist by its ID and return a promise.
     *
     * @param int|string $id The ID of the Playlist.
     *
     * @throws \BlueBillywig\Exception\HTTPRequestException
     */
    public function deleteAsync(int|string $id): PromiseInterface
    {
        return $this->sdk->sendRequestAsync(
            new Request('DELETE', "/sapi/playlist/$id")
        );
    }
}
