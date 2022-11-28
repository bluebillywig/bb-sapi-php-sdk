<?php

namespace Tests\Unit;

use BlueBillywig\Authentication\EmptyAuthenticator;
use BlueBillywig\Exception\HTTPClientErrorRequestException;
use BlueBillywig\Exception\HTTPRequestException;
use BlueBillywig\Exception\HTTPServerErrorRequestException;
use BlueBillywig\Request;
use BlueBillywig\Sdk;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

class SdkTest extends \Codeception\Test\Unit
{
    use \Codeception\AssertThrows;

    public function testWithRPCTokenAuthentication()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new GuzzleResponse(200)
        ]);

        $sdk = Sdk::withRPCTokenAuthentication("my-publication", 1, "my-shared-secret", ['handler' => $mockHandler]);

        $sdk->sendRequest(new Request("GET", "/sapi/test-method"));

        $this->assertNotEmpty($mockHandler->getLastRequest()->getHeader("rpctoken"));
    }

    public function testSendRequestAsync()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new GuzzleResponse(200)
        ]);

        $sdk = new Sdk("my-publication", new EmptyAuthenticator(), ['handler' => $mockHandler]);

        $promise = $sdk->sendRequestAsync(new Request("GET", "/sapi/test-method"));
        $response = $promise->wait();

        $this->assertDoesNotThrow(HTTPRequestException::class, [$response, "assertIsOk"]);
    }

    public function testSendRequestAsyncNotFound()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new GuzzleResponse(404)
        ]);

        $sdk = new Sdk("my-publication", new EmptyAuthenticator(), ['handler' => $mockHandler]);

        $promise = $sdk->sendRequestAsync(new Request("GET", "/sapi/test-method"));
        $response = $promise->wait();

        $this->assertThrowsWithMessage(HTTPClientErrorRequestException::class, "Not Found", [$response, "assertIsOk"]);
    }

    public function testSendRequestAsyncInternalServerError()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new GuzzleResponse(500)
        ]);

        $sdk = new Sdk("my-publication", new EmptyAuthenticator(), ['handler' => $mockHandler]);

        $promise = $sdk->sendRequestAsync(new Request("GET", "/sapi/test-method"));
        $response = $promise->wait();

        $this->assertThrowsWithMessage(HTTPServerErrorRequestException::class, "Internal Server Error", [$response, "assertIsOk"]);
    }

    public function testParseSapiRequestUri()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new GuzzleResponse(200)
        ]);

        $sdk = new Sdk("my-publication", new EmptyAuthenticator(), ['handler' => $mockHandler]);

        $sdk->sendRequest(new Request("GET", "/sapi/test-method"));

        $this->assertEquals(strval($mockHandler->getLastRequest()->getUri()), "https://my-publication.bbvms.com/sapi/test-method");
    }

    public function testNotParseNonSapiRequestUri()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new GuzzleResponse(200)
        ]);

        $sdk = new Sdk("my-publication", new EmptyAuthenticator(), ['handler' => $mockHandler]);

        $sdk->sendRequest(new Request("GET", "https://www.bluebillywig.com/"));

        $this->assertEquals(strval($mockHandler->getLastRequest()->getUri()), "https://www.bluebillywig.com/");
    }
}
