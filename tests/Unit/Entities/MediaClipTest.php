<?php

namespace BlueBillywig\Tests\Unit\Entities;

use BlueBillywig\Authentication\EmptyAuthenticator;
use BlueBillywig\Sdk;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

class MediaClipTest extends \Codeception\Test\Unit
{
    use \Codeception\AssertThrows;

    public function testInitializeUploadNonExistingFile()
    {
        $sdk = new Sdk("my-publication", new EmptyAuthenticator());

        $mediaClipPath = "./path/to/a/non/existing/mediaclip/file";

        $this->assertThrowsWithMessage(
            \InvalidArgumentException::class,
            "File $mediaClipPath is not a file or does not exist.",
            function () use ($sdk, $mediaClipPath) {
                $sdk->mediaclip->initializeUploadAsync($mediaClipPath)->wait();
            }
        );
    }

    public function testInitializeUpload()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new GuzzleResponse(200)
        ]);
        $sdk = new Sdk("my-publication", new EmptyAuthenticator(), ['handler' => $mockHandler]);

        $mediaClipPath = __DIR__ . "/../../Support/Data/blank.mp4";

        $expected = [
            "filename" => "blank.mp4",
            "filesize" => 665,
            "contenttype" => "video/mp4"
        ];

        $sdk->mediaclip->initializeUploadAsync($mediaClipPath)->wait();

        $requestUri = $mockHandler->getLastRequest()->getUri();
        parse_str($requestUri->getQuery(), $queryParams);

        $this->assertEmpty(array_diff_assoc($expected, $queryParams));
        $this->assertTrue(str_starts_with(strval($requestUri), "https://my-publication.bbvms.com/sapi/mediaclip/0/upload?"));
        $this->assertEquals("GET", $mockHandler->getLastRequest()->getMethod());
    }

    public function testInitializeUploadWithMediaClipId()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new GuzzleResponse(200)
        ]);
        $sdk = new Sdk("my-publication", new EmptyAuthenticator(), ['handler' => $mockHandler]);

        $mediaClipPath = __DIR__ . "/../../Support/Data/blank.mp4";
        $mediaClipId = 1;

        $expected = [
            "filename" => "blank.mp4",
            "filesize" => 665,
            "contenttype" => "video/mp4",
            "clipid" => $mediaClipId
        ];

        $sdk->mediaclip->initializeUploadAsync($mediaClipPath, $mediaClipId)->wait();

        $requestUri = $mockHandler->getLastRequest()->getUri();
        parse_str($requestUri->getQuery(), $queryParams);

        $this->assertEmpty(array_diff_assoc($expected, $queryParams));
        $this->assertTrue(str_starts_with(strval($requestUri), "https://my-publication.bbvms.com/sapi/mediaclip/0/upload?"));
        $this->assertEquals("GET", $mockHandler->getLastRequest()->getMethod());
    }

    public function testAbortUpload()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new GuzzleResponse(200)
        ]);
        $sdk = new Sdk("my-publication", new EmptyAuthenticator(), ['handler' => $mockHandler]);

        $s3FileKey = "/prefix/my-video.mp4";
        $s3UploadId = "12345";

        $expected = [
            "s3filekey" => $s3FileKey,
            "s3uploadid" => $s3UploadId
        ];

        $sdk->mediaclip->abortUploadAsync($s3FileKey, $s3UploadId)->wait();

        $requestUri = $mockHandler->getLastRequest()->getUri();
        parse_str($requestUri->getQuery(), $queryParams);

        $this->assertEmpty(array_diff_assoc($expected, $queryParams));
        $this->assertTrue(str_starts_with(strval($requestUri), "https://my-publication.bbvms.com/sapi/mediaclip/0/abortUpload?"));
        $this->assertEquals("PUT", $mockHandler->getLastRequest()->getMethod());
    }

    public function testCompleteUpload()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new GuzzleResponse(200)
        ]);
        $sdk = new Sdk("my-publication", new EmptyAuthenticator(), ['handler' => $mockHandler]);

        $s3FileKey = "/prefix/my-video.mp4";
        $s3UploadId = "12345";
        $s3Parts = [
            [
                "ETag" => "12345",
                "PartNumber" => "1",
            ],
            [
                "ETag" => "12346",
                "PartNumber" => "2",
            ],
            [
                "ETag" => "12347",
                "PartNumber" => "3",
            ],
        ];

        $expected = [
            "s3FileKey" => $s3FileKey,
            "s3UploadId" => $s3UploadId,
            "s3Parts" => $s3Parts
        ];

        $sdk->mediaclip->completeUploadAsync($s3FileKey, $s3UploadId, $s3Parts)->wait();

        $requestUri = $mockHandler->getLastRequest()->getUri();
        parse_str($requestUri->getQuery(), $queryParams);

        $this->assertJsonStringEqualsJsonString(json_encode($expected), $mockHandler->getLastRequest()->getBody()->getContents());
        $this->assertEquals("https://my-publication.bbvms.com/sapi/mediaclip/0/completeUpload", strval($requestUri));
        $this->assertEquals("PUT", $mockHandler->getLastRequest()->getMethod());
    }

    public function testGet()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new GuzzleResponse(200)
        ]);
        $sdk = new Sdk("my-publication", new EmptyAuthenticator(), ['handler' => $mockHandler]);

        $mediaClipId = 1;

        $expected = [
            "includejobs" => 1
        ];

        $sdk->mediaclip->getAsync($mediaClipId)->wait();

        $requestUri = $mockHandler->getLastRequest()->getUri();
        parse_str($requestUri->getQuery(), $queryParams);

        $this->assertEmpty(array_diff_assoc($expected, $queryParams));
        $this->assertTrue(str_starts_with(strval($requestUri), "https://my-publication.bbvms.com/sapi/mediaclip/$mediaClipId?"));
        $this->assertEquals("GET", $mockHandler->getLastRequest()->getMethod());
    }

    public function testGetWithLang()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new GuzzleResponse(200)
        ]);
        $sdk = new Sdk("my-publication", new EmptyAuthenticator(), ['handler' => $mockHandler]);

        $mediaClipId = 1;
        $language = "en";

        $expected = [
            "includejobs" => 0,
            "lang" => $language
        ];

        $sdk->mediaclip->getAsync($mediaClipId, $language, false)->wait();

        $requestUri = $mockHandler->getLastRequest()->getUri();
        parse_str($requestUri->getQuery(), $queryParams);

        $this->assertEmpty(array_diff_assoc($expected, $queryParams));
        $this->assertTrue(str_starts_with(strval($requestUri), "https://my-publication.bbvms.com/sapi/mediaclip/$mediaClipId?"));
        $this->assertEquals("GET", $mockHandler->getLastRequest()->getMethod());
    }

    public function testCreate()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new GuzzleResponse(200)
        ]);
        $sdk = new Sdk("my-publication", new EmptyAuthenticator(), ['handler' => $mockHandler]);

        $expected = [
            "softsave" => 0,
        ];
        $props = [
            "title" => "My Mediaclip"
        ];

        $sdk->mediaclip->createAsync($props)->wait();

        $requestUri = $mockHandler->getLastRequest()->getUri();
        parse_str($requestUri->getQuery(), $queryParams);

        $this->assertEmpty(array_diff_assoc($expected, $queryParams));
        $this->assertJsonStringEqualsJsonString(json_encode($props), $mockHandler->getLastRequest()->getBody()->getContents());
        $this->assertTrue(str_starts_with(strval($requestUri), "https://my-publication.bbvms.com/sapi/mediaclip?"));
        $this->assertEquals("PUT", $mockHandler->getLastRequest()->getMethod());
    }

    public function testCreateWithLang()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new GuzzleResponse(200)
        ]);
        $sdk = new Sdk("my-publication", new EmptyAuthenticator(), ['handler' => $mockHandler]);

        $language = "en";

        $expected = [
            "softsave" => 1,
            "lang" => $language
        ];
        $props = [
            "title" => "My Mediaclip"
        ];

        $sdk->mediaclip->createAsync($props, true, $language)->wait();

        $requestUri = $mockHandler->getLastRequest()->getUri();
        parse_str($requestUri->getQuery(), $queryParams);

        $this->assertEmpty(array_diff_assoc($expected, $queryParams));
        $this->assertJsonStringEqualsJsonString(json_encode($props), $mockHandler->getLastRequest()->getBody()->getContents());
        $this->assertTrue(str_starts_with(strval($requestUri), "https://my-publication.bbvms.com/sapi/mediaclip?"));
        $this->assertEquals("PUT", $mockHandler->getLastRequest()->getMethod());
    }

    public function testUpdate()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new GuzzleResponse(200)
        ]);
        $sdk = new Sdk("my-publication", new EmptyAuthenticator(), ['handler' => $mockHandler]);

        $mediaClipId = 1;

        $expected = [
            "softsave" => 0,
        ];
        $props = [
            "title" => "My Mediaclip"
        ];

        $sdk->mediaclip->updateAsync($mediaClipId, $props)->wait();

        $requestUri = $mockHandler->getLastRequest()->getUri();
        parse_str($requestUri->getQuery(), $queryParams);

        $this->assertEmpty(array_diff_assoc($expected, $queryParams));
        $this->assertJsonStringEqualsJsonString(json_encode($props), $mockHandler->getLastRequest()->getBody()->getContents());
        $this->assertTrue(str_starts_with(strval($requestUri), "https://my-publication.bbvms.com/sapi/mediaclip/$mediaClipId?"));
        $this->assertEquals("PUT", $mockHandler->getLastRequest()->getMethod());
    }

    public function testUpdateWithLang()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new GuzzleResponse(200)
        ]);
        $sdk = new Sdk("my-publication", new EmptyAuthenticator(), ['handler' => $mockHandler]);

        $mediaClipId = 1;
        $language = "en";

        $expected = [
            "softsave" => 1,
            "lang" => $language
        ];
        $props = [
            "title" => "My Mediaclip"
        ];

        $sdk->mediaclip->updateAsync($mediaClipId, $props, true, $language)->wait();

        $requestUri = $mockHandler->getLastRequest()->getUri();
        parse_str($requestUri->getQuery(), $queryParams);

        $this->assertEmpty(array_diff_assoc($expected, $queryParams));
        $this->assertJsonStringEqualsJsonString(json_encode($props), $mockHandler->getLastRequest()->getBody()->getContents());
        $this->assertTrue(str_starts_with(strval($requestUri), "https://my-publication.bbvms.com/sapi/mediaclip/$mediaClipId?"));
        $this->assertEquals("PUT", $mockHandler->getLastRequest()->getMethod());
    }
}
