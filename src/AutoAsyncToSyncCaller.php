<?php

namespace BlueBillywig;

trait AutoAsyncToSyncCaller
{
    /**
     * Allows for calling of (undefined) "synchronous" methods from defined "asynchronous" methods.
     * This is tried for every called method that does not end with "Async".
     */
    public function __call($name, $arguments)
    {
        $asyncMethodName = $name . 'Async';
        if (
            substr($name, -5) !== 'Async' &&
            !method_exists($this, $name) &&
            method_exists($this, $asyncMethodName)
        ) {
            return call_user_func_array(
                [$this, $asyncMethodName],
                $arguments
            )->wait();
        }
        return call_user_func_array([$this, $name], $arguments);
    }
}
