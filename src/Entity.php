<?php

namespace BlueBillywig;

use BlueBillywig\Sdk;

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

    public function __call($name, $arguments)
    {
        $asyncMethodName = $name . "Async";
        if (substr($name, -5) !== "Async" && method_exists($this, $asyncMethodName)) {
            return call_user_func_array([$this, $asyncMethodName], $arguments)->wait();
        }
        return call_user_func_array([$this, $name], $arguments);
    }

    protected function getSdk(): Sdk
    {
        if (!isset($this->sdk)) {
            $this->sdk = $this->parent->getSdk();
        }
        return $this->sdk;
    }
}
