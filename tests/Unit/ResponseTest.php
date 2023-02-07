<?php

namespace BlueBillywig\Tests\Unit;

use BlueBillywig\Exception\HTTPClientErrorRequestException;
use BlueBillywig\Exception\HTTPRequestException;
use BlueBillywig\Exception\HTTPServerErrorRequestException;
use BlueBillywig\Request;
use BlueBillywig\Response;
use BlueBillywig\Util\HTTPStatusCodeCategory;
use GuzzleHttp\Psr7\Utils as Psr7Utils;

class ResponseTest extends \Codeception\Test\Unit
{
    use \Codeception\AssertThrows;

    public function testIsOk()
    {
        $request = new Request("GET", "https://www.bluebillywig.com/");
        $response = new Response($request);
        foreach (range(200, 299) as $number) {
            $this->assertTrue($response->withStatus($number)->isOk());
        }
    }

    /**
     * @example [100, 199]
     * @example [300, 399]
     * @example [400, 499]
     * @example [500, 599]
     */
    public function testIsNotOk($startCode, $endCode)
    {
        $request = new Request("GET", "https://www.bluebillywig.com/");
        $response = new Response($request);
        foreach (range($startCode, $endCode) as $code) {
            $this->assertNotTrue($response->withStatus($code)->isOk());
        }
    }

    public function testAssertIsOk()
    {
        $request = new Request("GET", "https://www.bluebillywig.com/");
        $response = new Response($request);
        foreach (range(200, 299) as $code) {
            $this->assertDoesNotThrow(HTTPRequestException::class, function () use ($response, $code) {
                $response->withStatus($code)->assertIsOk();
            });
        }
    }

    /**
     * @dataProvider assertIsNotOkDataProvider
     */
    public function testAssertIsNotOk(int $startCode, int $endCode, string $exceptionClass)
    {
        $request = new Request("GET", "https://www.bluebillywig.com/");
        $response = new Response($request);
        foreach (range($startCode, $endCode) as $code) {
            $this->assertThrows($exceptionClass, function () use ($response, $code) {
                $response->withStatus($code)->assertIsOk();
            });
        }
    }

    protected function assertIsNotOkDataProvider(): array
    {
        return [
            [100, 199, HTTPRequestException::class],
            [300, 399, HTTPRequestException::class],
            [400, 499, HTTPClientErrorRequestException::class],
            [500, 599, HTTPServerErrorRequestException::class]
        ];
    }

    /**
     * @dataProvider getStatusCodeCategoryDataProvider
     */
    public function testGetStatusCodeCategory(int $startCode, int $endCode, HTTPStatusCodeCategory $statusCategory)
    {
        $request = new Request("GET", "https://www.bluebillywig.com/");
        $response = new Response($request);
        foreach (range($startCode, $endCode) as $code) {
            $this->assertEquals($statusCategory, $response->withStatus($code)->getStatusCodeCategory());
        }
    }

    protected function getStatusCodeCategoryDataProvider(): array
    {
        return [
            [100, 199, HTTPStatusCodeCategory::Informational],
            [200, 299, HTTPStatusCodeCategory::Successful],
            [300, 399, HTTPStatusCodeCategory::Redirection],
            [400, 499, HTTPStatusCodeCategory::ClientError],
            [500, 599, HTTPStatusCodeCategory::ServerError]
        ];
    }

    public function testAllOk()
    {
        $request = new Request("GET", "https://www.bluebillywig.com/");
        $responses = [];
        foreach (range(200, 299) as $code) {
            $responses[] = new Response($request, $code);
        }
        $this->assertTrue(Response::allOk($responses));
    }

    /**
     * @example [100, 199]
     * @example [300, 399]
     * @example [400, 499]
     * @example [500, 599]
     */
    public function testNotAllOk(int $startCode, int $endCode)
    {
        $request = new Request("GET", "https://www.bluebillywig.com/");
        foreach (range($startCode, $endCode) as $code) {
            $responses = [
                new Response($request, 200),
                new Response($request, 200),
                new Response($request, $code),
                new Response($request, 200)
            ];
            $this->assertNotTrue(Response::allOk($responses));
        }
    }

    public function testAssertAllOk()
    {
        $request = new Request("GET", "https://www.bluebillywig.com/");
        $responses = [];
        foreach (range(200, 299) as $code) {
            $responses[] = new Response($request, $code);
        }
        $this->assertDoesNotThrow(HTTPRequestException::class, function () use ($responses) {
            Response::assertAllOk($responses);
        });
    }

    /**
     * @dataProvider assertIsNotOkDataProvider
     */
    public function testAssertNotAllOk(int $startCode, int $endCode, string $exceptionClass)
    {
        $request = new Request("GET", "https://www.bluebillywig.com/");
        foreach (range($startCode, $endCode) as $code) {
            $responses = [
                new Response($request, 200),
                new Response($request, 200),
                new Response($request, $code),
                new Response($request, 200)
            ];
            $this->assertThrows($exceptionClass, function () use ($responses) {
                Response::assertAllOk($responses);
            });
        }
    }

    /**
     * @example [100, 199]
     * @example [300, 399]
     * @example [400, 499]
     * @example [500, 599]
     */
    public function testGetFailedResponses(int $startCode, int $endCode)
    {
        $request = new Request("GET", "https://www.bluebillywig.com/");
        foreach (range($startCode, $endCode) as $code) {
            $responses = [
                new Response($request, 200),
                new Response($request, 200),
                new Response($request, $code),
                new Response($request, 200),
                new Response($request, $code),
                new Response($request, 200)
            ];
            $this->assertEquals(2, count(iterator_to_array(Response::getFailedResponses($responses))));
        }
    }

    public function testGetRequest()
    {
        $request = new Request("GET", "https://www.bluebillywig.com/");
        $response = new Response($request);
        $this->assertEquals($request, $response->getRequest());
    }

    public function testGetJsonBody()
    {
        $this->assertJsonContents(
            [
                "object1" => [
                    "field1" => "value1",
                    "field2" => "value2"
                ],
                "object2" => [
                    "object3" => [
                        "field3" => "value3",
                        "field4" => "value4",
                    ],
                    "list1" => [
                        "listValue1",
                        "listValue2",
                        "listValue3"
                    ]
                ]
            ],
            'getJsonBody'
        );
    }

    protected function assertJsonContents(array $jsonContents, string $decodeMethodName)
    {
        $json = json_encode($jsonContents);
        $request = new Request("GET", "https://www.bluebillywig.com/");
        $response = new Response($request, 200, [], Psr7Utils::streamFor($json));
        $array = call_user_func([$response, $decodeMethodName], true);
        $this->assertIsArray($array);
        $this->assertJsonStringEqualsJsonString($json, json_encode($array));
        $response->getBody()->rewind();
        $object = call_user_func([$response, $decodeMethodName], false);
        $this->assertIsObject($object);
        $this->assertJsonStringEqualsJsonString($json, json_encode($object));
    }

    public function testGetEmptyJsonBody()
    {
        $request = new Request("GET", "https://www.bluebillywig.com/");
        $response = new Response($request);
        $response = $response->withBody(Psr7Utils::streamFor(''));
        $this->assertNull($response->getJsonBody());
    }

    public function testGetXmlBody()
    {
        $this->assertXmlContents(
            [
                "field1" => "value1",
                "field2" => "value2",
                "field3" => "value3"
            ],
            'getXmlBody'
        );
    }

    protected function assertXmlContents(array $xmlContents, string $decodeMethodName)
    {
        $request = new Request("GET", "https://www.bluebillywig.com/");
        $xml = new \SimpleXMLElement('<root/>');
        $xmlContents = array_flip($xmlContents);
        array_walk_recursive($xmlContents, [$xml, 'addChild']);
        $xmlString = $xml->asXML();
        $response = new Response($request, 200, [], Psr7Utils::streamFor($xmlString));
        $array = call_user_func([$response, $decodeMethodName], true);
        $this->assertIsArray($array);
        $contentsXml = new \SimpleXMLElement('<root/>');
        $array = array_flip($array);
        array_walk_recursive($array, [$contentsXml, 'addChild']);
        $this->assertXmlStringEqualsXmlString($xmlString, $contentsXml->asXML());
        $response->getBody()->rewind();
        $object = call_user_func([$response, $decodeMethodName], false);
        $this->assertIsObject($object);
        $this->assertXmlStringEqualsXmlString($xmlString, $object->asXML());
    }

    public function testGetEmptyXmlBody()
    {
        $request = new Request("GET", "https://www.bluebillywig.com/");
        $response = new Response($request);
        $response = $response->withBody(Psr7Utils::streamFor(''));
        $this->assertNull($response->getXmlBody());
    }

    public function testGetDecodedBody()
    {
        $contents = [
            "field1" => "value1",
            "field2" => "value2",
            "field3" => "value3"
        ];
        $this->assertJsonContents($contents, 'getDecodedBody');
        $this->assertXmlContents($contents, 'getDecodedBody');
    }

    public function testGetIncorrectDecodedBody()
    {
        $request = new Request("GET", "https://www.bluebillywig.com/");
        $response = new Response($request, 200, [], Psr7Utils::streamFor('some incorrect value to decode to JSON or XML'));
        $this->assertThrowsWithMessage(\RuntimeException::class, "Could not load body as JSON or XML.", function () use ($response) {
            $response->getDecodedBody();
        });
    }
}
