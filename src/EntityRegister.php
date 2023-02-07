<?php

namespace BlueBillywig;

abstract class EntityRegister
{
    /**
     * @var array<array<string,string>|string> List of nested entities or mappings of entity names and entities
     */
    protected static array $entitiesCls;

    /**
     * @var EntityRegisterItem[]
     */
    private array $entities;

    /**
     * @var array<string,string> $entities Optional mapping of entity names and entities.
     */
    public function __construct(array $entities = [])
    {
        $this->entities = $entities;
        foreach (static::$entitiesCls ?? [] as $entityCls) {
            if (empty($entityCls)) {
                throw new \UnexpectedValueException("Empty entity mapping given.");
            }
            if (array_keys($entityCls) === range(0, count($entityCls) - 1)) {
                $this->registerEntity($entityCls[0]);
            } else {
                $this->registerEntity(array_values($entityCls)[0], array_keys($entityCls)[0]);
            }
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
        throw new \InvalidArgumentException("Cannot get inaccessible or undefined property $name.");
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

        $entityCallName = $nameOverride ?? lcfirst($refl->getShortName());
        if (!in_array($entityCallName, $this->entities)) {
            $this->entities[$entityCallName] = new EntityRegisterItem($entityCls, $this);
        }
    }

    /**
     * Retrieve the Sdk instance to which this EntityRegister is linked.
     */
    protected abstract function getSdk(): Sdk;
}
