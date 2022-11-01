<?php

namespace BlueBillywig\Authentication;

use BlueBillywig\Util\HOTP;
use Psr\Http\Message\RequestInterface;

class RPCTokenAuthenticator extends Authenticator
{
    private $tokenId;
    private $sharedSecret;

    public function __construct($tokenId, $sharedSecret)
    {
        $this->tokenId = $tokenId;
        $this->sharedSecret = $sharedSecret;
    }

    public function __invoke(RequestInterface $request)
    {
        return $request->withHeader("rpctoken", "{$this->tokenId}-{$this->calculateToken()}");
    }

    private function calculateToken($expire = null)
    {
        if (!is_numeric($expire)) {
            $expire = 120;
        }
        $result = HOTP::generateByTime($this->sharedSecret, $expire, time());
        return $result->toString();
    }
}
