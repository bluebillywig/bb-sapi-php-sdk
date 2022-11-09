<?php

namespace BlueBillywig;

use BlueBillywig\Exception\NotImplementedException;
use BlueBillywig\Sdk;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * @method Response get(int|string $id) Retrieve an Entity by its ID. @see getAsync
 */
abstract class Entity extends EntityRegister
{
    protected EntityRegister $parent;
    protected Sdk $sdk;

    public function __construct(EntityRegister $parent)
    {
        $this->parent = $parent;
        $this->getSdk();
        parent::__construct();
    }

    /**
     * Allows for calling of (undefined) "synchronous" methods from defined "asynchronous" methods.
     * This is tried for every called method that does not end with "Async".
     */
    public function __call($name, $arguments)
    {
        $asyncMethodName = $name . "Async";
        if (substr($name, -5) !== "Async" && method_exists($this, $asyncMethodName)) {
            return call_user_func_array([$this, $asyncMethodName], $arguments)->wait();
        }
        return call_user_func_array([$this, $name], $arguments);
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
     * Retrieve an Entity by its ID and return a promise.
     *
     * @param int|string $id The ID of the Entity.
     */
    public function getAsync(int|string $id): PromiseInterface
    {
        throw new NotImplementedException("This method is not implemented.");
    }
}
