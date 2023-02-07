<?php

namespace BlueBillywig\Entities;

use BlueBillywig\Entity;
use BlueBillywig\Request;
use BlueBillywig\Response;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;

/**
 * Representation of the MediaClip resource on the Blue Billywig SAPI.
 *
 * @property-read \BlueBillywig\Helpers\MediaClipHelper $helper
 * @method Response initializeUpload(string|\SplFileInfo $mediaClipPath, ?int $mediaClipId = null) Retrieve the presigned URLs for a MediaClip file upload. @see initializeUploadAsync
 * @method Response abortUpload(string $s3FileKey, string $s3UploadId) Abort the multipart upload of a MediaClip file. @see abortUploadAsync
 * @method Response completeUpload(string $s3FileKey, string $s3UploadId, array $s3Parts) Complete the multipart upload of a MediaClip file. @see completeUploadAsync
 * @method Response get(int|string $id, ?string $lang = null, bool $includeJobs = true) Retrieve a MediaClip by its ID. @see getAsync
 * @method Response create(array $props, bool $softSave = false, ?string $lang = null) Create a MediaClip. @see createAsync
 * @method Response update(int|string $id, array $props, bool $softSave = false, ?string $lang = null) Update a MediaClip by its ID and return a promise. @see updateAsync
 */
class MediaClip extends Entity
{
    protected static string $helperCls = \BlueBillywig\Helpers\MediaClipHelper::class;

    /**
     * Retrieve the presigned URLs for a MediaClip file upload and return a promise.
     *
     * @param string|\SplFileInfo $mediaClipPath The path to the MediaClip file that will be uploaded.
     * @param ?int $mediaClipId ID of a MediaClip that should be given when adding or replacing the MediaClip file on an already created MediaClip.
     *
     * @throws \InvalidArgumentException
     * @throws \BlueBillyWig\Exception\HTTPRequestException
     */
    public function initializeUploadAsync(string|\SplFileInfo $mediaClipPath, ?int $mediaClipId = null): PromiseInterface
    {
        if (!($mediaClipPath instanceof \SplFileInfo)) {
            $mediaClipPath = new \SplFileInfo(strval($mediaClipPath));
        }
        if (!$mediaClipPath->isFile()) {
            throw new \InvalidArgumentException("File $mediaClipPath is not a file or does not exist.");
        }
        $contentType = mime_content_type(strval($mediaClipPath));
        $requestOptions = [RequestOptions::QUERY => [
            "filename" => $mediaClipPath->getFilename(),
            "filesize" => $mediaClipPath->getSize(),
            "contenttype" => $contentType
        ]];
        if (!empty($mediaClipId)) {
            $requestOptions[RequestOptions::QUERY]["clipid"] = $mediaClipId;
        }

        return $this->sdk->sendRequestAsync(new Request(
            "GET",
            "/sapi/mediaclip/0/upload"
        ), $requestOptions);
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
            RequestOptions::QUERY => [
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
     *
     * @throws \BlueBillyWig\Exception\HTTPRequestException
     */
    public function completeUploadAsync(string $s3FileKey, string $s3UploadId, array $s3Parts): PromiseInterface
    {
        $requestOptions = [
            RequestOptions::JSON => [
                "s3FileKey" => $s3FileKey,
                "s3UploadId" => $s3UploadId,
                "s3Parts" => $s3Parts
            ],
        ];
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
        $requestOptions = [RequestOptions::QUERY => [
            "includejobs" => $includeJobs
        ]];
        if (!empty($lang)) {
            $requestOptions[RequestOptions::QUERY]['lang'] = $lang;
        }
        return $this->sdk->sendRequestAsync(new Request(
            "GET",
            "/sapi/mediaclip/$id"
        ), $requestOptions);
    }

    /**
     * Create a MediaClip and return a promise.
     *
     * @param array $props The properties of the MediaClip to create.
     * @param bool $softSave Whether to save only after checking if the content of the MediaClip has changed after being fetched, defaults to false.
     * @param ?string $lang The language of the MediaClip.
     */
    public function createAsync(array $props, bool $softSave = false, ?string $lang = null): PromiseInterface
    {
        $requestOptions = [
            RequestOptions::QUERY => [
                "softsave" => $softSave
            ],
            RequestOptions::JSON => $props
        ];
        if (!empty($lang)) {
            $requestOptions[RequestOptions::QUERY]['lang'] = $lang;
        }
        return $this->sdk->sendRequestAsync(new Request(
            "PUT",
            "/sapi/mediaclip"
        ), $requestOptions);
    }

    /*
     * Update a MediaClip by its ID and return a promise.
     * Note that all the MediaClip metadata is updated, so first a retrieval should be done and all the metadata should be given, even the fields that have not changed.
     *
     * @param int|string $id The ID of the MediaClip.
     * @param array $props The properties of the MediaClip to update.
     * @param bool $softSave Whether to save only after checking if the content of the MediaClip has changed after being fetched, defaults to false.
     * @param ?string $lang The language of the MediaClip.
     */
    public function updateAsync(int|string $id, array $props, bool $softSave = false, ?string $lang = null): PromiseInterface
    {
        $requestOptions = [
            RequestOptions::QUERY => [
                "softsave" => $softSave
            ],
            RequestOptions::JSON => $props
        ];
        if (!empty($lang)) {
            $requestOptions[RequestOptions::QUERY]['lang'] = $lang;
        }
        return $this->sdk->sendRequestAsync(new Request(
            "PUT",
            "/sapi/mediaclip/$id"
        ), $requestOptions);
    }
}
