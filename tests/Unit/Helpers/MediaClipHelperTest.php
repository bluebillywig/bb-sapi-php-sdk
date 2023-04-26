<?php

namespace BlueBillywig\Tests\Unit\Helpers;

use BlueBillywig\Authentication\EmptyAuthenticator;
use BlueBillywig\Exception\HTTPClientErrorRequestException;
use BlueBillywig\Exception\HTTPServerErrorRequestException;
use BlueBillywig\Sdk;
use BlueBillywig\Tests\Support\GuzzleTestServer;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

class MediaClipHelperTest extends \Codeception\Test\Unit
{
    use \Codeception\AssertThrows;

    public static function _setUpBeforeClass()
    {
        GuzzleTestServer::start();
    }

    public static function _tearDownAfterClass()
    {
        GuzzleTestServer::stop();
    }

    public function _before()
    {
        GuzzleTestServer::flush();
    }

    public function testExecuteUploadSingleChunk()
    {
        GuzzleTestServer::enqueue([new GuzzleResponse(200)]);
        $sdk = new Sdk('my-publication', new EmptyAuthenticator(), [
            'base_uri' => GuzzleTestServer::$url,
        ]);

        $mediaClipPath = __DIR__ . '/../../Support/Data/blank.mp4';

        $this->assertTrue(
            $sdk->mediaclip->helper
                ->executeUploadAsync($mediaClipPath, [
                    'chunks' => 1,
                    'presignedUrls' => [
                        [
                            'presignedUrl' =>
                            GuzzleTestServer::$url . 'presigned-url',
                            'chunkSize' => 1,
                        ],
                    ],
                ])
                ->wait()
        );

        $requests = GuzzleTestServer::received();
        $this->assertNotEmpty($requests);
        $this->assertEquals(1, count($requests));
        $request = $requests[0];
        $this->assertEquals('PUT', $request->getMethod());
        $this->assertEquals('/presigned-url', $request->getUri()->getPath());
        $this->assertEquals(1, $request->getBody()->getSize());
    }

    public function testExecuteUploadMultipleChunks()
    {
        GuzzleTestServer::enqueue([
            new GuzzleResponse(200, ['ETag' => ['some-etag-1']]),
            new GuzzleResponse(200, ['ETag' => ['some-etag-2']]),
            new GuzzleResponse(200, ['ETag' => ['some-etag-3']]),
            new GuzzleResponse(200),
        ]);
        $sdk = new Sdk('my-publication', new EmptyAuthenticator(), [
            'base_uri' => GuzzleTestServer::$url,
        ]);

        $mediaClipPath = __DIR__ . '/../../Support/Data/blank.mp4';

        $this->assertTrue(
            $sdk->mediaclip->helper
                ->executeUploadAsync($mediaClipPath, [
                    'key' => '/prefix/blank.mp4',
                    'uploadId' => '12345',
                    'chunks' => 3,
                    'presignedUrls' => [
                        [
                            'presignedUrl' =>
                            GuzzleTestServer::$url .
                                'presigned-url/1?partNumber=1',
                            'chunkSize' => 1,
                        ],
                        [
                            'presignedUrl' =>
                            GuzzleTestServer::$url .
                                'presigned-url/2?partNumber=2',
                            'chunkSize' => 1,
                        ],
                        [
                            'presignedUrl' =>
                            GuzzleTestServer::$url .
                                'presigned-url/3?partNumber=3',
                            'chunkSize' => 1,
                        ],
                    ],
                ])
                ->wait()
        );

        $requests = GuzzleTestServer::received();

        $assertChunkRequest = function (
            \Psr\Http\Message\RequestInterface $chunkRequest,
            int $partNumber
        ) {
            $this->assertEquals('PUT', $chunkRequest->getMethod());
            $this->assertEquals(
                "/presigned-url/$partNumber",
                $chunkRequest->getUri()->getPath()
            );
            $this->assertEquals(
                "partNumber=$partNumber",
                $chunkRequest->getUri()->getQuery()
            );
            $this->assertEquals(1, $chunkRequest->getBody()->getSize());
        };

        $this->assertNotEmpty($requests);
        $this->assertEquals(4, count($requests));

        $chunk1Request = $requests[0];
        $assertChunkRequest($chunk1Request, 1);
        $chunk2Request = $requests[1];
        $assertChunkRequest($chunk2Request, 2);
        $chunk3Request = $requests[2];
        $assertChunkRequest($chunk3Request, 3);

        $completeRequest = $requests[3];
        $this->assertEquals('PUT', $completeRequest->getMethod());
        $this->assertEquals(
            '/sapi/mediaclip/0/completeUpload',
            $completeRequest->getUri()->getPath()
        );
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                's3FileKey' => '/prefix/blank.mp4',
                's3UploadId' => '12345',
                's3Parts' => [
                    [
                        'ETag' => 'some-etag-1',
                        'PartNumber' => '1',
                    ],
                    [
                        'ETag' => 'some-etag-2',
                        'PartNumber' => '2',
                    ],
                    [
                        'ETag' => 'some-etag-3',
                        'PartNumber' => '3',
                    ],
                ],
            ]),
            $completeRequest->getBody()->getContents()
        );
    }

    public function testExecuteUploadInvalidMediaClipPath()
    {
        $sdk = new Sdk('my-publication', new EmptyAuthenticator());

        $mediaClipPath = './path/to/a/non/existing/mediaclip/file';

        $this->assertThrowsWithMessage(
            \InvalidArgumentException::class,
            "File {$mediaClipPath} is not a file or does not exist.",
            function () use ($sdk, $mediaClipPath) {
                $sdk->mediaclip->helper
                    ->executeUploadAsync($mediaClipPath, [])
                    ->wait();
            }
        );
    }

    public function testAbortUpload()
    {
        GuzzleTestServer::enqueue([
            new GuzzleResponse(200, ['ETag' => ['some-etag-1']]),
            new GuzzleResponse(200, ['ETag' => ['some-etag-2']]),
            new GuzzleResponse(500),
            new GuzzleResponse(200),
        ]);
        $sdk = new Sdk('my-publication', new EmptyAuthenticator(), [
            'base_uri' => GuzzleTestServer::$url,
        ]);

        $mediaClipPath = __DIR__ . '/../../Support/Data/blank.mp4';

        $this->assertThrows(
            HTTPServerErrorRequestException::class,
            function () use ($sdk, $mediaClipPath) {
                $sdk->mediaclip->helper
                    ->executeUploadAsync($mediaClipPath, [
                        'key' => '/prefix/blank.mp4',
                        'uploadId' => '12345',
                        'chunks' => 3,
                        'presignedUrls' => [
                            [
                                'presignedUrl' =>
                                GuzzleTestServer::$url .
                                    'presigned-url/1?partNumber=1',
                                'chunkSize' => 1,
                            ],
                            [
                                'presignedUrl' =>
                                GuzzleTestServer::$url .
                                    'presigned-url/2?partNumber=2',
                                'chunkSize' => 1,
                            ],
                            [
                                'presignedUrl' =>
                                GuzzleTestServer::$url .
                                    'presigned-url/3?partNumber=3',
                                'chunkSize' => 1,
                            ],
                        ],
                    ])
                    ->wait();
            }
        );

        $requests = GuzzleTestServer::received();

        $this->assertEquals(4, count($requests));
        $abortRequest = $requests[3];
        $this->assertEquals('PUT', $abortRequest->getMethod());
        $this->assertEquals(
            '/sapi/mediaclip/0/abortUpload',
            $abortRequest->getUri()->getPath()
        );
        $this->assertEquals(
            's3filekey=/prefix/blank.mp4&s3uploadid=12345',
            urldecode($abortRequest->getUri()->getQuery())
        );
    }

    public function testGetUploadProgressUploadNotStarted()
    {
        GuzzleTestServer::enqueue([
            new GuzzleResponse(404),
            new GuzzleResponse(404),
        ]);
        $sdk = new Sdk('my-publication', new EmptyAuthenticator(), [
            'base_uri' => GuzzleTestServer::$url,
        ]);

        $uploadProgress = $sdk->mediaclip->helper
            ->getUploadProgressAsync(
                GuzzleTestServer::$url . 'list-part',
                GuzzleTestServer::$url . 'head-object',
                5
            )
            ->wait();

        $this->assertEquals(0, $uploadProgress);

        $requests = GuzzleTestServer::received();
        $this->assertEquals(2, count($requests));
        $listPartRequest = $requests[0];
        $this->assertEquals('GET', $listPartRequest->getMethod());
        $this->assertEquals(
            '/list-part',
            $listPartRequest->getUri()->getPath()
        );
        $headObjectRequest = $requests[1];
        $this->assertEquals('HEAD', $headObjectRequest->getMethod());
        $this->assertEquals(
            '/head-object',
            $headObjectRequest->getUri()->getPath()
        );
    }

    public function testGetUploadProgressUploadCompleted()
    {
        GuzzleTestServer::enqueue([
            new GuzzleResponse(404),
            new GuzzleResponse(200),
        ]);
        $sdk = new Sdk('my-publication', new EmptyAuthenticator(), [
            'base_uri' => GuzzleTestServer::$url,
        ]);

        $uploadProgress = $sdk->mediaclip->helper
            ->getUploadProgressAsync(
                GuzzleTestServer::$url . 'list-part',
                GuzzleTestServer::$url . 'head-object',
                5
            )
            ->wait();

        $this->assertEquals(100, $uploadProgress);

        $requests = GuzzleTestServer::received();
        $this->assertEquals(2, count($requests));
        $listPartRequest = $requests[0];
        $this->assertEquals('GET', $listPartRequest->getMethod());
        $this->assertEquals(
            '/list-part',
            $listPartRequest->getUri()->getPath()
        );
        $headObjectRequest = $requests[1];
        $this->assertEquals('HEAD', $headObjectRequest->getMethod());
        $this->assertEquals(
            '/head-object',
            $headObjectRequest->getUri()->getPath()
        );
    }

    public function testGetUploadProgressUploadSinglePartFinished()
    {
        GuzzleTestServer::enqueue([
            new GuzzleResponse(
                200,
                [],
                json_encode([
                    'Part' => [
                        'PartNumber' => 1,
                    ],
                ])
            ),
        ]);
        $sdk = new Sdk('my-publication', new EmptyAuthenticator(), [
            'base_uri' => GuzzleTestServer::$url,
        ]);

        $uploadProgress = $sdk->mediaclip->helper
            ->getUploadProgressAsync(
                GuzzleTestServer::$url . 'list-part',
                GuzzleTestServer::$url . 'head-object',
                5
            )
            ->wait();

        $this->assertEquals(20, $uploadProgress);

        $requests = GuzzleTestServer::received();
        $this->assertEquals(1, count($requests));
        $listPartRequest = $requests[0];
        $this->assertEquals('GET', $listPartRequest->getMethod());
        $this->assertEquals(
            '/list-part',
            $listPartRequest->getUri()->getPath()
        );
    }

    public function testGetUploadProgressUploadSomePartsFinished()
    {
        GuzzleTestServer::enqueue([
            new GuzzleResponse(
                200,
                [],
                json_encode([
                    'Part' => [[], [], []],
                ])
            ),
        ]);
        $sdk = new Sdk('my-publication', new EmptyAuthenticator(), [
            'base_uri' => GuzzleTestServer::$url,
        ]);

        $uploadProgress = $sdk->mediaclip->helper
            ->getUploadProgressAsync(
                GuzzleTestServer::$url . 'list-part',
                GuzzleTestServer::$url . 'head-object',
                5
            )
            ->wait();

        $this->assertEquals(60, $uploadProgress);

        $requests = GuzzleTestServer::received();
        $this->assertEquals(1, count($requests));
        $listPartRequest = $requests[0];
        $this->assertEquals('GET', $listPartRequest->getMethod());
        $this->assertEquals(
            '/list-part',
            $listPartRequest->getUri()->getPath()
        );
    }

    public function testGetUploadProgressUploadAllPartsFinished()
    {
        GuzzleTestServer::enqueue([
            new GuzzleResponse(
                200,
                [],
                json_encode([
                    'Part' => [[], [], [], [], []],
                ])
            ),
        ]);
        $sdk = new Sdk('my-publication', new EmptyAuthenticator(), [
            'base_uri' => GuzzleTestServer::$url,
        ]);

        $uploadProgress = $sdk->mediaclip->helper
            ->getUploadProgressAsync(
                GuzzleTestServer::$url . 'list-part',
                GuzzleTestServer::$url . 'head-object',
                5
            )
            ->wait();

        $this->assertEquals(100, $uploadProgress);

        $requests = GuzzleTestServer::received();
        $this->assertEquals(1, count($requests));
        $listPartRequest = $requests[0];
        $this->assertEquals('GET', $listPartRequest->getMethod());
        $this->assertEquals(
            '/list-part',
            $listPartRequest->getUri()->getPath()
        );
    }

    public function testGetUploadProgressDelayedRequest()
    {
        GuzzleTestServer::enqueue([
            new GuzzleResponse(404),
            new GuzzleResponse(200),
        ]);
        $sdk = new Sdk('my-publication', new EmptyAuthenticator(), [
            'base_uri' => GuzzleTestServer::$url,
        ]);

        $startTime = hrtime(true);
        $uploadProgress = $sdk->mediaclip->helper
            ->getUploadProgressAsync(
                GuzzleTestServer::$url . 'list-part',
                GuzzleTestServer::$url . 'head-object',
                5,
                2000
            )
            ->wait();
        $endTime = hrtime(true);

        $this->assertGreaterThanOrEqual(2000, ($endTime - $startTime) / 1e-6);

        $this->assertEquals(100, $uploadProgress);
    }

    public function testGetUploadProgressIncorrectResponse()
    {
        GuzzleTestServer::enqueue([new GuzzleResponse(400)]);
        $sdk = new Sdk('my-publication', new EmptyAuthenticator(), [
            'base_uri' => GuzzleTestServer::$url,
        ]);

        $this->assertThrows(
            HTTPClientErrorRequestException::class,
            function () use ($sdk) {
                $sdk->mediaclip->helper
                    ->getUploadProgressAsync(
                        GuzzleTestServer::$url . 'list-part',
                        GuzzleTestServer::$url . 'head-object',
                        5
                    )
                    ->wait();
            }
        );
    }

    public function testUploadProgressGenerator()
    {
        GuzzleTestServer::enqueue([
            new GuzzleResponse(404),
            new GuzzleResponse(404),
            new GuzzleResponse(
                200,
                [],
                json_encode([
                    'Part' => [[]],
                ])
            ),
            new GuzzleResponse(
                200,
                [],
                json_encode([
                    'Part' => [[], []],
                ])
            ),
            new GuzzleResponse(
                200,
                [],
                json_encode([
                    'Part' => [[], [], []],
                ])
            ),
            new GuzzleResponse(
                200,
                [],
                json_encode([
                    'Part' => [[], [], [], []],
                ])
            ),
            new GuzzleResponse(404),
            new GuzzleResponse(200),
        ]);
        $sdk = new Sdk('my-publication', new EmptyAuthenticator(), [
            'base_uri' => GuzzleTestServer::$url,
        ]);

        $startTime = hrtime(true);
        $progress = iterator_to_array(
            $sdk->mediaclip->helper->uploadProgressGenerator(
                GuzzleTestServer::$url . 'list-part',
                GuzzleTestServer::$url . 'head-object',
                5,
                500
            )
        );
        $endTime = hrtime(true);

        $this->assertGreaterThanOrEqual(2500, ($endTime - $startTime) / 1e-6);

        $this->assertEmpty(
            array_diff_assoc([0, 20, 40, 60, 80, 100], $progress)
        );
    }

    public function testGetSourcePathRelative()
    {
        $mediaClip = [
            "src" => "/some/source/of/mediaclip.mp4"
        ];
        GuzzleTestServer::enqueue([
            new GuzzleResponse(200, [], json_encode($mediaClip))
        ]);
        $sdk = new Sdk('my-publication', new EmptyAuthenticator(), [
            'base_uri' => GuzzleTestServer::$url,
        ]);

        $mediaClipId = 1;

        $sourcePathRelative = $sdk->mediaclip->helper->getSourcePathAsync($mediaClipId, false)->wait();
        $requests = GuzzleTestServer::received();

        $this->assertEquals(1, count($requests));
        $this->assertEquals($mediaClip['src'], $sourcePathRelative);
    }

    public function testGetSourcePathAbsolute()
    {
        $mediaClip = [
            "src" => "/some/source/of/mediaclip.mp4"
        ];
        $publicationData = [
            "defaultMediaAssetPath" => "https://my-cfn.bluebillywig.com"
        ];
        GuzzleTestServer::enqueue([
            new GuzzleResponse(200, [], json_encode($mediaClip)),
            new GuzzleResponse(200, [], json_encode($publicationData))
        ]);
        $sdk = new Sdk('my-publication', new EmptyAuthenticator(), [
            'base_uri' => GuzzleTestServer::$url,
        ]);

        $mediaClipId = 1;

        $sourcePathAbsolute = $sdk->mediaclip->helper->getSourcePathAsync($mediaClipId, true)->wait();
        $requests = GuzzleTestServer::received();

        $this->assertEquals(2, count($requests));
        $this->assertEquals($publicationData["defaultMediaAssetPath"] . $mediaClip['src'], $sourcePathAbsolute);
    }
}
