<?php

namespace BlueBillywig;

/**
 * Base Helper class.
 * Helper classes contain more complex logic than the Entity they are bound to.
 * They combine different functions on the Entity and may return other values than Response objects.
 *
 * @property-read Entity $entity
 * @property-read Sdk $sdk
 */
abstract class Helper
{
    use AutoAsyncToSyncCaller;

    private readonly Entity $entity;
    private readonly Sdk $sdk;

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
        $this->sdk = $entity->getSdk();
    }

    public function __get($name)
    {
        if ($name === 'entity') {
            return $this->entity;
        } elseif ($name === 'sdk') {
            return $this->sdk;
        }
        throw new \InvalidArgumentException("Cannot get inaccessible or undefined property $name.");
    }
}
