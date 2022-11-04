<?php

namespace BlueBillywig;

use Closure;

class EntityRegisterItem
{
    private string $cls;
    private EntityRegister $parent;
    private ?Closure $factory;
    private ?Entity $instance;

    public function __construct(string $cls, EntityRegister $parent, ?callable $factory = null)
    {
        $this->cls = $cls;
        $this->parent = $parent;
        if (!empty($factory)) {
            $this->factory = Closure::fromCallable($factory);
        }
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->getInstance(), $name], $arguments);
    }

    public function __get($name)
    {
        return $this->getInstance()->$name;
    }

    public function __set($name, $value)
    {
        $this->getInstance()->$name = $value;
    }

    private function getInstance()
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
