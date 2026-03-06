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
 * Representation of the Channel resource on the Blue Billywig SAPI
 *
 * @method Response list(int $limit = 15, int $offset = 0, string $sort = 'createddate desc') Retrieve a list of Channels. @see listAsync
 * @method Response get(int|string $id) Retrieve a Channel by its ID. @see getAsync
 * @method Response create(array $props) Create a Channel. @see createAsync
 * @method Response update(int|string $id, array $props) Update a Channel by its ID. @see updateAsync
 * @method Response delete(int|string $id) Delete a Channel by its ID. @see deleteAsync
 */
class Channel extends Entity implements Listable, Gettable, Creatable, Updatable, Deletable
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

    /**
     * Create a Channel and return a promise.
     *
     * @param array{
     *     config?: array{
     *         playIn?: string,
     *         detailPageConfig?: array{
     *             playerAlignment?: string,
     *             withinBorder?: bool,
     *             backgroundColor?: string,
     *             showThumbnailAsBackground?: bool,
     *             enableBackgroundBlur?: bool,
     *             showRelatedItems?: bool,
     *             relatedItemsLayout?: string
     *         },
     *         blocks?: array
     *     }
     * } $props The properties of the Channel to create.
     */
    public function createAsync(array $props): PromiseInterface
    {
        return $this->sdk->sendRequestAsync(new Request(
            "PUT",
            "/sapi/channel"
        ), [
            RequestOptions::JSON => $props
        ]);
    }

    /**
     * Update a Channel by its ID and return a promise.
     *
     * @param int|string $id The ID of the Channel.
     * @param array{
     *     config?: array{
     *         playIn?: string,
     *         detailPageConfig?: array{
     *             playerAlignment?: string,
     *             withinBorder?: bool,
     *             backgroundColor?: string,
     *             showThumbnailAsBackground?: bool,
     *             enableBackgroundBlur?: bool,
     *             showRelatedItems?: bool,
     *             relatedItemsLayout?: string
     *         },
     *         blocks?: array
     *     }
     * } $props The properties of the Channel to update.
     */
    public function updateAsync(int|string $id, array $props): PromiseInterface
    {
        return $this->sdk->sendRequestAsync(new Request(
            "PUT",
            "/sapi/channel/$id"
        ), [
            RequestOptions::JSON => $props
        ]);
    }

    /**
     * Delete a Channel by its ID and return a promise.
     *
     * @param int|string $id The ID of the Channel.
     *
     * @throws \BlueBillyWig\Exception\HTTPRequestException
     */
    public function deleteAsync(int|string $id): PromiseInterface
    {
        return $this->sdk->sendRequestAsync(
            new Request('DELETE', "/sapi/channel/$id")
        );
    }
}
