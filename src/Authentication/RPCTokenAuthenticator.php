<?php

namespace BlueBillywig\Authentication;

use BlueBillywig\Util\HOTP;
use Psr\Http\Message\RequestInterface;

class RPCTokenAuthenticator extends Authenticator
{
    private int $tokenId;
    private string $sharedSecret;

    public function __construct(int $tokenId, string $sharedSecret)
    {
        $this->tokenId = $tokenId;
        $this->sharedSecret = $sharedSecret;
    }

    public function __invoke(RequestInterface $request): RequestInterface
    {
        return $request->withHeader("rpctoken", "{$this->tokenId}-{$this->calculateToken()}");
    }

    private function calculateToken($expire = null): string
    {
        if (!is_numeric($expire)) {
            $expire = 120;
        }
        $result = HOTP::generateByTime($this->sharedSecret, $expire, time());
        return $result->toString();
    }
}
