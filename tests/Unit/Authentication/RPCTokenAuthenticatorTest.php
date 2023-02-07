<?php

namespace BlueBillywig\Tests\Unit\Authentication;

use BlueBillywig\Authentication\RPCTokenAuthenticator;
use BlueBillywig\Request;
use BlueBillywig\VMSRPC\HOTP;

class RPCTokenAuthenticatorTest extends \Codeception\Test\Unit
{
    public function testCalculateTokenHeader()
    {
        $rpcTokenAuthenticator = new RPCTokenAuthenticator('1', 'some-secret');

        $request = new Request('GET', 'https://www.bluebillywig.com');

        $authenticatedRequest = $rpcTokenAuthenticator($request);

        $rpcTokenHeaderValue = $authenticatedRequest->getHeader('rpctoken');
        $this->assertEquals(1, count($rpcTokenHeaderValue));
        $rpcToken = $rpcTokenHeaderValue[0];
        [$tokenId, $token] = explode('-', $rpcToken);
        $this->assertEquals($tokenId, '1');

        $result = HOTP::generateByTimeWindow('some-secret', 120, -1, 1, time());
        $correctHOTPKey = null;
        foreach ($result as $member) {
            $member = $member->toString();
            if ($member === $token) {
                $correctHOTPKey = $member;
            }
        }
        $this->assertNotNull($correctHOTPKey);
    }
}
