<?php

namespace BlueBillywig\Authentication;

use Psr\Http\Message\RequestInterface;

abstract class Authenticator
{
    /**
     * The authentication (headers) to apply to the RequestInterface.
     * This is used as a MiddleWare.
     */
    public abstract function __invoke(RequestInterface $request): RequestInterface;
}
