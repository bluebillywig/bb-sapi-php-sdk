<?php

namespace BlueBillywig\Authentication;

use BlueBillywig\VMSRPC\HOTP;
use Psr\Http\Message\RequestInterface;

class RPCTokenAuthenticator extends Authenticator
{
    public function __construct(
        private readonly int $tokenId,
        private readonly string $sharedSecret,
        private readonly int $tokenExpiration = 120
    ) {
    }

    public function __invoke(RequestInterface $request): RequestInterface
    {
        return $request->withHeader("rpctoken", "{$this->tokenId}-{$this->calculateToken()}");
    }

    private function calculateToken(): string
    {
        $result = HOTP::generateByTime($this->sharedSecret, $this->tokenExpiration, time());
        return $result->toString();
    }
}
