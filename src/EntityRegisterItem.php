<?php

namespace BlueBillywig;

class EntityRegisterItem
{
    private string $cls;
    private EntityRegister $parent;
    private ?\Closure $factory;
    private ?Entity $instance;

    public function __construct(string $cls, EntityRegister $parent, ?callable $factory = null)
    {
        $this->cls = $cls;
        $this->parent = $parent;
        if (!empty($factory)) {
            $this->factory = \Closure::fromCallable($factory);
        }
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
                $this->instance = ($this->factory)();
            } else {
                $this->instance = new ($this->cls)($this->parent);
            }
        }
        return $this->instance;
    }
}
