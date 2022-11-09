<?php

namespace BlueBillywig\Entities;

use BlueBillywig\Entity;
use BlueBillywig\Exception\HTTPRequestException;
use BlueBillywig\Request;
use BlueBillywig\Response;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Psr7\Utils as Psr7Utils;
use InvalidArgumentException;

/**
 * Representation of the MediaClip entity in the OVP.
 *
 * @method Response fullUpload(string|\SplFileInfo $mediaClipPath, ?int $mediaClipId = null) Execute the full flow for uploading a MediaClip file. @see fullUploadAsync
 * @method Response upload(string|\SplFileInfo $mediaClipPath) Retrieve the presigned URLs for a MediaClip file upload. @see uploadAsync
 * @method Response abortUpload(string $s3FileKey, string $s3UploadId) Abort the multipart upload of a MediaClip file. @see abortUploadAsync
 * @method Response completeUpload(string $s3FileKey, string $s3UploadId, array $s3Parts, int $mediaClipId = null) Complete the multipart upload of a MediaClip file. @see completeUploadAsync
 * @method Response get(int|string $id, ?string $lang = null, bool $includeJobs = true) Retrieve a MediaClip by its ID. @see getAsync
 */
class MediaClip extends Entity
{
    /**
     * Execute the full flow for uploading a MediaClip file and return a promise.
     * This combines the uploadAsync, abortUploadAsync, and completeUploadAsync methods.
     *
     * @param string|\SplFileInfo $mediaClipPath The path to the MediaClip file that will be uploaded.
     * @param ?int $mediaClipId ID of a MediaClip that should only be given when replacing the MediaClip file.
     *
     * @throws \InvalidArgumentException
     * @throws \BlueBillyWig\Exception\HTTPRequestException
     */
    public function fullUploadAsync(string|\SplFileInfo $mediaClipPath, ?int $mediaClipId = null): PromiseInterface
    {
        if (!($mediaClipPath instanceof \SplFileInfo)) {
            $mediaClipPath = new \SplFileInfo(strval($mediaClipPath));
        }

        $response = $this->uploadAsync($mediaClipPath)->wait();
        $response->assertIsOk();

        $data = json_decode($response->getBody()->getContents(), true);
        if ($data['chunks'] === 1) {
            // PutObject command is performed instead of UploadPart, so we can directly return this promise
            return $this->performUpload($mediaClipPath, $data['presignedUrls'][0]);
        }

        return $this->performMultiPartUpload($mediaClipPath, $data['presignedUrls'])->then(
            function (array $responses) use ($data, $mediaClipId) {
                try {
                    Response::assertAllOk($responses);
                } catch (HTTPRequestException $e) {
                    // No need to return a promise, since an exception is immediately thrown afterwards
                    $this->abortUploadAsync($data['key'], $data['uploadId'])->wait()->assertIsOk();
                    throw $e;
                }
                $parts = [];
                foreach ($responses as $response) {
                    $parts[] = [
                        "ETag" => trim($response->getHeader("ETag")[0], "\""),
                        "PartNumber" => $response->getRequest()->getQueryParam("partNumber")
                    ];
                }
                return $this->completeUploadAsync($data['key'], $data['uploadId'], $parts, $mediaClipId);
            }
        );
    }

    private function performMultiPartUpload(\SplFileInfo $mediaClipPath, array $presignedUrls): PromiseInterface
    {
        $promises = [];
        foreach ($presignedUrls as $presignedUrl) {
            $promises[] = $this->performUpload($mediaClipPath, $presignedUrl);
        }
        return Utils::all($promises);
    }

    private function performUpload(\SplFileInfo $mediaClipPath, array $presignedUrl): PromiseInterface
    {
        $fileObject = $mediaClipPath->openFile();
        $fileObject->fseek($presignedUrl['offset'] ?? 0);
        return $this->sdk->sendRequestAsync(new Request(
            "PUT",
            $presignedUrl['presignedUrl'],
            [],
            Psr7Utils::streamFor($fileObject->fread($presignedUrl['chunkSize'] ?? $fileObject->getSize()))
        ));
    }

    /**
     * Retrieve the presigned URLs for a MediaClip file upload and return a promise.
     *
     * @param string|\SplFileInfo $mediaClipPath The path to the MediaClip file that will be uploaded.
     *
     * @throws \InvalidArgumentException
     * @throws \BlueBillyWig\Exception\HTTPRequestException
     */
    public function uploadAsync(string|\SplFileInfo $mediaClipPath): PromiseInterface
    {
        if (!($mediaClipPath instanceof \SplFileInfo)) {
            $mediaClipPath = new \SplFileInfo(strval($mediaClipPath));
        }
        if (!$mediaClipPath->isFile()) {
            throw new \InvalidArgumentException("File {$mediaClipPath} is not a file or does not exist.");
        }

        return $this->sdk->sendRequestAsync(new Request(
            "GET",
            "/sapi/mediaclip/0/upload"
        ), [
            "query" => [
                "filename" => $mediaClipPath->getFilename(),
                "filesize" => $mediaClipPath->getSize()
            ]
        ]);
    }

    /**
     * Abort the multipart upload of a MediaClip file and return a promise.
     *
     * @param string $s3FileKey Key of the object for which the multipart upload was initiated.
     * @param string $s3UploadId Upload ID that identifies the multipart upload.
     *
     * @throws \BlueBillyWig\Exception\HTTPRequestException
     */
    public function abortUploadAsync(string $s3FileKey, string $s3UploadId): PromiseInterface
    {
        return $this->sdk->sendRequestAsync(new Request(
            "PUT",
            "/sapi/mediaclip/0/abortUpload"
        ), [
            "query" => [
                "s3filekey" => $s3FileKey,
                "s3uploadid" => $s3UploadId,
            ]
        ]);
    }

    /**
     * Complete the multipart upload of a MediaClip file and return a promise.
     *
     * @param string $s3FileKey Key of the object for which the multipart upload was initiated.
     * @param string $s3UploadId Upload ID that identifies the multipart upload.
     * @param array[] $s3Parts Details of the parts that were uploaded.
     * @param ?int $mediaClipId ID of a MediaClip that should only be given when replacing the MediaClip file.
     *
     * @throws \BlueBillyWig\Exception\HTTPRequestException
     */
    public function completeUploadAsync(string $s3FileKey, string $s3UploadId, array $s3Parts, ?int $mediaClipId = null): PromiseInterface
    {
        $requestOptions = ["query" => [
            "json" => json_encode([
                "s3FileKey" => $s3FileKey,
                "s3UploadId" => $s3UploadId,
                "s3Parts" => $s3Parts
            ]),
        ]];
        if (!empty($mediaClipId)) {
            $requestOptions["query"]["clipId"] = $mediaClipId;
        }
        return $this->sdk->sendRequestAsync(new Request(
            "PUT",
            "/sapi/mediaclip/0/completeUpload"
        ), $requestOptions);
    }

    /**
     * Retrieve a MediaClip by its ID and return a promise.
     *
     * @param int|string $id The ID of the MediaClip.
     * @param ?string $lang The language of the MediaClip.
     * @param bool $includeJobs When **TRUE**, the media job details list is included in the result.
     *
     * @throws \BlueBillyWig\Exception\HTTPRequestException
     */
    public function getAsync(int|string $id, ?string $lang = null, bool $includeJobs = true): PromiseInterface
    {
        $requestOptions = ["query" => [
            "includejobs" => $includeJobs
        ]];
        if (!empty($lang)) {
            $requestOptions['query']['lang'] = $lang;
        }
        return $this->sdk->sendRequestAsync(new Request(
            "GET",
            "/sapi/mediaclip/$id"
        ), $requestOptions);
    }
}
