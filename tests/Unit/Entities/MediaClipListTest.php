<?php

namespace BlueBillywig\Tests\Unit\Entities;

use BlueBillywig\Authentication\EmptyAuthenticator;
use BlueBillywig\Sdk;
use GuzzleHttp\Psr7\Response as GuzzleResponse;


class MediaClipListTest extends \Codeception\Test\Unit
{
    public function testGet()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new GuzzleResponse(200)
        ]);
        $sdk = new Sdk("my-publication", new EmptyAuthenticator(), ['handler' => $mockHandler]);

        $mediaClipListId = 1;

        $sdk->mediacliplist->getAsync($mediaClipListId)->wait();

        $requestUri = $mockHandler->getLastRequest()->getUri();

        $this->assertEquals("https://my-publication.bbvms.com/sapi/cliplist/$mediaClipListId", strval($requestUri));
    }
}
