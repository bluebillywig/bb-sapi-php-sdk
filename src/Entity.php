<?php

namespace BlueBillywig;

use BlueBillywig\Exception\NotImplementedException;
use BlueBillywig\Sdk;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Base Entity class.
 * An Entity represents a resource on the Blue Billywig SAPI.
 * Entity class methods should only call one API method and should always return a Response object.
 *
 * @property-read \BlueBillywig\Helper $helper
 * @property-read \BlueBillywig\Sdk $sdk
 * @method Response get(int|string $id) Retrieve an Entity by its ID. @see getAsync
 * @method Response delete(int|string $id) Delete an Entity by its ID. @see deleteAsync
 * @method Response update(int|string $id, array $props) Update an Entity by its ID. @see updateAsync
 * @method Response create(int|string $id, array $props) Create an Entity. @see createAsync
 */
abstract class Entity extends EntityRegister
{
    use AutoAsyncToSyncCaller;

    private readonly EntityRegister $parent;

    private Sdk $sdk;
    private Helper $helper;

    protected static string $helperCls;

    public function __construct(EntityRegister $parent)
    {
        $this->parent = $parent;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        if ($name === 'sdk') {
            return $this->getSdk();
        } elseif ($name === 'helper') {
            if (!isset(static::$helperCls)) {
                throw new \Exception(static::class . ' does not have a helper defined.');
            } elseif (!isset($this->helper)) {
                $this->helper = new (static::$helperCls)($this);
            }
            return $this->helper;
        }
        return parent::__get($name);
    }

    /**
     * Retrieve the Sdk instance to which this Entity is linked.
     */
    protected function getSdk(): Sdk
    {
        if (!isset($this->sdk)) {
            $this->sdk = $this->parent->getSdk();
        }
        return $this->sdk;
    }

    /**
     * @codeCoverageIgnore
     * Retrieve an Entity by its ID and return a promise.
     *
     * @param int|string $id The ID of the Entity.
     */
    public function getAsync(int|string $id): PromiseInterface
    {
        throw new NotImplementedException("This method is not implemented.");
    }

    /**
     * @codeCoverageIgnore
     * Delete an Entity by its ID and return a promise.
     *
     * @param int|string $id The ID of the Entity.
     */
    public function deleteAsync(int|string $id): PromiseInterface
    {
        throw new NotImplementedException("This method is not implemented.");
    }

    /**
     * @codeCoverageIgnore
     * Update an Entity by its ID and return a promise.
     *
     * @param int|string $id The ID of the Entity.
     * @param array $props The properties of the Entity to update.
     */
    public function updateAsync(int|string $id, array $props): PromiseInterface
    {
        throw new NotImplementedException("This method is not implemented.");
    }

    /**
     * @codeCoverageIgnore
     * Create an Entity and return a promise.
     *
     * @param array $props The properties of the Entity to create.
     */
    public function createAsync(array $props): PromiseInterface
    {
        throw new NotImplementedException("This method is not implemented.");
    }
}
