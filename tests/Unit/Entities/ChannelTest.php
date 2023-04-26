<?php

namespace BlueBillywig\Tests\Unit\Entities;

use BlueBillywig\Authentication\EmptyAuthenticator;
use BlueBillywig\Sdk;
use GuzzleHttp\Psr7\Response as GuzzleResponse;


class ChannelTest extends \Codeception\Test\Unit
{
    public function testList()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new GuzzleResponse(200)
        ]);
        $sdk = new Sdk("my-publication", new EmptyAuthenticator(), ['handler' => $mockHandler]);

        $limit = 15;
        $offset = 1;
        $sort = "createddate asc";

        $expected = [
            "limit" => $limit,
            "offset" => $offset,
            "sort" => $sort
        ];

        $sdk->channel->listAsync($limit, $offset, $sort)->wait();

        $requestUri = $mockHandler->getLastRequest()->getUri();

        parse_str($requestUri->getQuery(), $queryParams);

        $this->assertEmpty(array_diff_assoc($expected, $queryParams));
        $this->assertTrue(str_starts_with(strval($requestUri), "https://my-publication.bbvms.com/sapi/channel?"));
    }

    public function testGet()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new GuzzleResponse(200)
        ]);
        $sdk = new Sdk("my-publication", new EmptyAuthenticator(), ['handler' => $mockHandler]);

        $channelId = 1;

        $sdk->channel->getAsync($channelId)->wait();

        $requestUri = $mockHandler->getLastRequest()->getUri();

        $this->assertEquals("https://my-publication.bbvms.com/sapi/channel/$channelId", strval($requestUri));
    }
}
