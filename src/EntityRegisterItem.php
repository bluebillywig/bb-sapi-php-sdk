<?php

namespace BlueBillywig;

class EntityRegisterItem
{
    private readonly ?\Closure $factory;

    private ?Entity $instance;

    public function __construct(
        private readonly string $cls,
        private readonly EntityRegister $parent,
        ?callable $factory = null
    ) {
        $this->factory = !empty($factory) ? \Closure::fromCallable($factory) : null;
    }

    /**
     * Pass method calls to the Entity instance.
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->getInstance(), $name], $arguments);
    }

    /**
     * Pass property get calls to the Entity instance.
     */
    public function __get($name)
    {
        return $this->getInstance()->$name;
    }

    /**
     * Pass property set calls to the Entity instance.
     */
    public function __set($name, $value): void
    {
        $this->getInstance()->$name = $value;
    }

    private function getInstance(): Entity
    {
        if (!isset($this->instance)) {
            if (isset($this->factory)) {
                $this->instance = ($this->factory)($this->parent);
            } else {
                $this->instance = new ($this->cls)($this->parent);
            }
        }
        return $this->instance;
    }

    public function getEntityClass(): string
    {
        return $this->getInstance()::class;
    }
}
