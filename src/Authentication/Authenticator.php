<?php

namespace BlueBillywig\Authentication;

use Psr\Http\Message\RequestInterface;

abstract class Authenticator
{
    public abstract function __invoke(RequestInterface $request);
}
