<?php

namespace BlueBillywig\Tests\Unit\Entities;

use BlueBillywig\Authentication\EmptyAuthenticator;
use BlueBillywig\Sdk;
use GuzzleHttp\Psr7\Response as GuzzleResponse;


class PlaylistTest extends \Codeception\Test\Unit
{
    public function testList()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new GuzzleResponse(200)
        ]);
        $sdk = new Sdk("my-publication", new EmptyAuthenticator(), ['handler' => $mockHandler]);

        $limit = 10;
        $offset = 5;
        $sort = "createddate asc";

        $expected = [
            "limit" => $limit,
            "offset" => $offset,
            "sort" => $sort
        ];

        $sdk->playlist->listAsync($limit, $offset, $sort)->wait();

        $requestUri = $mockHandler->getLastRequest()->getUri();
        parse_str($requestUri->getQuery(), $queryParams);

        $this->assertEmpty(array_diff_assoc($expected, $queryParams));
        $this->assertTrue(str_starts_with(strval($requestUri), "https://my-publication.bbvms.com/sapi/playlist?"));
        $this->assertEquals("GET", $mockHandler->getLastRequest()->getMethod());
    }

    public function testGet()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new GuzzleResponse(200)
        ]);
        $sdk = new Sdk("my-publication", new EmptyAuthenticator(), ['handler' => $mockHandler]);

        $playlistId = 1;

        $sdk->playlist->getAsync($playlistId)->wait();

        $requestUri = $mockHandler->getLastRequest()->getUri();

        $this->assertEquals("https://my-publication.bbvms.com/sapi/playlist/$playlistId", strval($requestUri));
        $this->assertEquals("GET", $mockHandler->getLastRequest()->getMethod());
    }

    public function testCreate()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new GuzzleResponse(200)
        ]);
        $sdk = new Sdk("my-publication", new EmptyAuthenticator(), ['handler' => $mockHandler]);

        $props = [
            "title" => "My Playlist"
        ];

        $sdk->playlist->createAsync($props)->wait();

        $requestUri = $mockHandler->getLastRequest()->getUri();

        $this->assertJsonStringEqualsJsonString(json_encode($props), $mockHandler->getLastRequest()->getBody()->getContents());
        $this->assertEquals("https://my-publication.bbvms.com/sapi/playlist", strval($requestUri));
        $this->assertEquals("PUT", $mockHandler->getLastRequest()->getMethod());
    }

    public function testUpdate()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new GuzzleResponse(200)
        ]);
        $sdk = new Sdk("my-publication", new EmptyAuthenticator(), ['handler' => $mockHandler]);

        $playlistId = 1;
        $props = [
            "title" => "My Updated Playlist"
        ];

        $sdk->playlist->updateAsync($playlistId, $props)->wait();

        $requestUri = $mockHandler->getLastRequest()->getUri();

        $this->assertJsonStringEqualsJsonString(json_encode($props), $mockHandler->getLastRequest()->getBody()->getContents());
        $this->assertEquals("https://my-publication.bbvms.com/sapi/playlist/$playlistId", strval($requestUri));
        $this->assertEquals("PUT", $mockHandler->getLastRequest()->getMethod());
    }

    public function testDelete()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new GuzzleResponse(200)
        ]);
        $sdk = new Sdk("my-publication", new EmptyAuthenticator(), ['handler' => $mockHandler]);

        $playlistId = 1;

        $sdk->playlist->deleteAsync($playlistId)->wait();

        $requestUri = $mockHandler->getLastRequest()->getUri();

        $this->assertEquals("https://my-publication.bbvms.com/sapi/playlist/$playlistId", strval($requestUri));
        $this->assertEquals("DELETE", $mockHandler->getLastRequest()->getMethod());
    }

    public function testMediaClipListAlias()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new GuzzleResponse(200)
        ]);
        $sdk = new Sdk("my-publication", new EmptyAuthenticator(), ['handler' => $mockHandler]);

        $playlistId = 1;

        $sdk->mediacliplist->getAsync($playlistId)->wait();

        $requestUri = $mockHandler->getLastRequest()->getUri();

        $this->assertEquals("https://my-publication.bbvms.com/sapi/playlist/$playlistId", strval($requestUri));
        $this->assertEquals("GET", $mockHandler->getLastRequest()->getMethod());
    }
}
