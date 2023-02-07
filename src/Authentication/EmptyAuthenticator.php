<?php

namespace BlueBillywig\Authentication;

use BlueBillywig\Authentication\Authenticator;
use Psr\Http\Message\RequestInterface;

/**
 * @codeCoverageIgnore
 */
class EmptyAuthenticator extends Authenticator
{
    public function __invoke(RequestInterface $request): RequestInterface
    {
        return $request;
    }
}
