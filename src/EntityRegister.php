<?php

namespace BlueBillywig;

abstract class EntityRegister
{
    /**
     * @var string[]
     */
    protected static array $entitiesCls;

    /**
     * @var EntityRegisterItem[]
     */
    private array $entities;

    public function __construct(array $entities = [])
    {
        $this->entities = $entities;
        foreach (static::$entitiesCls ?? [] as $entityCls) {
            $this->registerEntity($entityCls);
        }
    }

    /**
     * Allows for automatic retrieving of a registered Entity.
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->entities)) {
            return $this->entities[$name];
        }
        return $this->$name;
    }

    /**
     * Register an Entity.
     *
     * @param string $entityCls The Entity class.
     * @param ?string $nameOverride Override the name that is used to call this Entity. By default the lowercase class name is used.
     */
    protected function registerEntity(string $entityCls, ?string $nameOverride = null): void
    {
        $refl = new \ReflectionClass($entityCls);

        if (!$refl->isSubclassOf(Entity::class)) {
            throw new \TypeError("Given entity $entityCls is not a subclass of " . Entity::class . ".");
        }

        $entityCallName = $nameOverride ?? strtolower($refl->getShortName());
        if (!in_array($entityCallName, $this->entities)) {
            $this->entities[$entityCallName] = new EntityRegisterItem($entityCls, $this);
        }
    }

    /**
     * Retrieve the Sdk instance to which this EntityRegister is linked.
     */
    protected abstract function getSdk(): Sdk;
}
