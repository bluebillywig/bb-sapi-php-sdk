<?php

namespace BlueBillywig;

abstract class EntityRegister
{
    protected static array $entitiesCls;

    private array $entities;

    public function __construct(array $entities = [])
    {
        $this->entities = $entities;
        foreach (static::$entitiesCls ?? [] as $entityCls) {
            $this->registerEntity($entityCls);
        }
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->entities)) {
            return $this->entities[$name];
        }
        return $this->$name;
    }

    protected function registerEntity($entityCls, $nameOverride = null)
    {
        $refl = new \ReflectionClass($entityCls);

        if (!$refl->isSubclassOf(Entity::class)) {
            throw new \TypeError("Given entity $entityCls is not a subclass of " . Entity::class . ".");
        }

        $entityCallName = $nameOverride ?? strtolower($refl->getShortName());
        if (!in_array($entityCallName, $this->entities)) {
            $this->entities[$entityCallName] = new $entityCls($this);
        }
    }

    protected abstract function getSdk(): Sdk;
}
