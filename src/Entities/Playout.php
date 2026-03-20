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
 * Representation of the Playout resource on the Blue Billywig SAPI.
 *
 * @method Response list(int $limit = 15, int $offset = 0, string $sort = 'createddate desc') Retrieve a list of Playouts. @see listAsync
 * @method Response get(int|string $id) Retrieve a Playout by its ID. @see getAsync
 * @method Response create(array $props) Create a Playout. @see createAsync
 * @method Response update(int|string $id, array $props) Update a Playout by its ID. @see updateAsync
 * @method Response delete(int|string $id) Delete a Playout by its ID. @see deleteAsync
 */
class Playout extends Entity implements Listable, Gettable, Creatable, Updatable, Deletable
{
    /**
     * Retrieve a list of Playouts and return a promise.
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
            new Request('GET', '/sapi/playout'),
            $requestOptions
        );
    }

    /**
     * Retrieve a Playout by its ID and return a promise.
     *
     * @param int|string $id The ID of the Playout.
     */
    public function getAsync(int|string $id): PromiseInterface
    {
        return $this->sdk->sendRequestAsync(
            new Request('GET', "/sapi/playout/$id")
        );
    }

    /**
     * Create a Playout and return a promise.
     *
     * @param array{
     *     name?: string,
     *     label?: string,
     *     status?: string,
     *     centerButtonType?: string,
     *     cornerRadius?: int,
     *     responsiveSizing?: bool,
     *     width?: int,
     *     height?: int,
     *     maxWidth?: int,
     *     autoHeight?: bool,
     *     aspectRatio?: string,
     *     backgroundColor?: string,
     *     foregroundColor?: string,
     *     widgetColor?: string,
     *     bgColor?: string,
     *     logoId?: int,
     *     logoAlign?: string,
     *     logoClickUrl?: string,
     *     controlBar?: bool,
     *     timeDisplay?: bool,
     *     timeLine?: bool,
     *     muteButton?: bool,
     *     volume?: bool,
     *     fullScreen?: bool,
     *     autoPlay?: bool,
     *     autoLoop?: bool,
     *     autoMute?: bool,
     *     title?: bool,
     *     fitmode?: string,
     *     commercials?: array
     * } $props The properties of the Playout to create.
     */
    public function createAsync(array $props): PromiseInterface
    {
        return $this->sdk->sendRequestAsync(new Request(
            "PUT",
            "/sapi/playout"
        ), [
            RequestOptions::JSON => $props
        ]);
    }

    /**
     * Update a Playout by its ID and return a promise.
     *
     * @param int|string $id The ID of the Playout.
     * @param array{
     *     name?: string,
     *     label?: string,
     *     status?: string,
     *     centerButtonType?: string,
     *     cornerRadius?: int,
     *     responsiveSizing?: bool,
     *     width?: int,
     *     height?: int,
     *     maxWidth?: int,
     *     autoHeight?: bool,
     *     aspectRatio?: string,
     *     backgroundColor?: string,
     *     foregroundColor?: string,
     *     widgetColor?: string,
     *     bgColor?: string,
     *     logoId?: int,
     *     logoAlign?: string,
     *     logoClickUrl?: string,
     *     controlBar?: bool,
     *     timeDisplay?: bool,
     *     timeLine?: bool,
     *     muteButton?: bool,
     *     volume?: bool,
     *     fullScreen?: bool,
     *     autoPlay?: bool,
     *     autoLoop?: bool,
     *     autoMute?: bool,
     *     title?: bool,
     *     fitmode?: string,
     *     commercials?: array
     * } $props The properties of the Playout to update.
     */
    public function updateAsync(int|string $id, array $props): PromiseInterface
    {
        return $this->sdk->sendRequestAsync(new Request(
            "PUT",
            "/sapi/playout/$id"
        ), [
            RequestOptions::JSON => $props
        ]);
    }

    /**
     * Delete a Playout by its ID and return a promise.
     *
     * @param int|string $id The ID of the Playout.
     *
     * @throws \BlueBillywig\Exception\HTTPRequestException
     */
    public function deleteAsync(int|string $id): PromiseInterface
    {
        return $this->sdk->sendRequestAsync(
            new Request('DELETE', "/sapi/playout/$id")
        );
    }
}
