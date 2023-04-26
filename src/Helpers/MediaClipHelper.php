<?php

namespace BlueBillywig\Helpers;

use BlueBillywig\Exception\HTTPRequestException;
use BlueBillywig\Helper;
use BlueBillywig\Request;
use BlueBillywig\Response;
use GuzzleHttp\Promise\Coroutine;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils as Psr7Utils;
use GuzzleHttp\RequestOptions;

/**
 * @property-read \BlueBillywig\Entities\MediaClip $entity
 * @method bool executeUpload(string|\SplFileInfo $mediaClipPath, array $uploadData) Execute the full flow for uploading a MediaClip file. @see executeUploadAsync
 * @method int|float getUploadProgress(string|Uri $listPartsUrl, string|Uri $headObjectUrl, int $partsCount, int $requestDelay = 0) Retrieve the progress of a MediaClip file upload. @see getUploadProgressAsync
 * @method string getSourcePath(int|string $mediaClipId, bool $absolute = true) Retrieve the path to the source file of a MediaClip. @see getMediaClipSourcePathAsync
 * @method string getAbsoluteVideoPath(string $relativeVideoPath) Parse a relative video path to an absolute one. @see getAbsoluteVideoPathAsync
 */
class MediaClipHelper extends Helper
{
    const DEFAULT_UPLOAD_PROGRESS_POLL_INTERVAL = 2000; //milliseconds

    /**
     * Execute the full flow for uploading a MediaClip file and return a promise.
     * This combines the abortUploadAsync and completeUploadAsync methods.
     *
     * @param string|\SplFileInfo $mediaClipPath The path to the MediaClip file that will be uploaded.
     * @param array $uploadData The upload data containing the parts to be uploaded.
     *
     * @throws \InvalidArgumentException
     * @throws \BlueBillyWig\Exception\HTTPRequestException
     */
    public function executeUploadAsync(string|\SplFileInfo $mediaClipPath, array $uploadData): PromiseInterface
    {
        if (!($mediaClipPath instanceof \SplFileInfo)) {
            $mediaClipPath = new \SplFileInfo(strval($mediaClipPath));
        }
        if (!$mediaClipPath->isFile()) {
            throw new \InvalidArgumentException("File {$mediaClipPath} is not a file or does not exist.");
        }
        return Coroutine::of(function () use ($mediaClipPath, $uploadData) {
            if ($uploadData['chunks'] === 1) {
                // PutObject command is performed instead of UploadPart, so we can directly return this promise
                $response = (yield $this->performUpload($mediaClipPath, $uploadData['presignedUrls'][0]));
                $response->assertIsOk();
                yield true;
                return;
            }

            $responses = (yield $this->performMultiPartUpload($mediaClipPath, $uploadData['presignedUrls']));
            try {
                Response::assertAllOk($responses);
            } catch (HTTPRequestException $e) {
                $response = (yield $this->entity->abortUploadAsync($uploadData['key'], $uploadData['uploadId']));
                $response->assertIsOk();
                throw $e;
            }
            $parts = [];
            foreach ($responses as $response) {
                $parts[] = [
                    "ETag" => trim($response->getHeader("ETag")[0], "\""),
                    "PartNumber" => $response->getRequest()->getQueryParam("partNumber")
                ];
            }
            $response = (yield $this->entity->completeUploadAsync($uploadData['key'], $uploadData['uploadId'], $parts));
            $response->assertIsOk();
            yield true;
        });
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
     * Retrieve the progress of a MediaClip file upload and return a promise.
     *
     * @param string|Uri $listPartsUrl The (presigned) URL for retrieving parts of the MediaClip file that were uploaded.
     * @param string|Uri $headObjectUrl The (presigned) URL for retrieving metadata of the MediaClip file that was uploaded.
     * @param int $partsCount The total number of parts that are being uploaded.
     * @param int $requestDelay The delay in milliseconds before the request should be send, defaults to 0.
     *
     * @throws \BlueBillywig\Exception\HTTPRequestException
     */
    public function getUploadProgressAsync(string|Uri $listPartsUrl, string|Uri $headObjectUrl, int $partsCount, int $requestDelay = 0): PromiseInterface
    {
        return Coroutine::of(function () use ($listPartsUrl, $headObjectUrl, $partsCount, $requestDelay) {
            $options = [];
            if ($requestDelay > 0) {
                $options[RequestOptions::DELAY] = $requestDelay;
            }
            $response = (yield $this->sdk->sendRequestAsync(new Request(
                'GET',
                $listPartsUrl
            ), $options));
            if ($response->getStatusCode() === 404) {
                $response = (yield $this->sdk->sendRequestAsync(new Request(
                    'HEAD',
                    $headObjectUrl
                )));
                if ($response->getStatusCode() === 404) {
                    // Could not find the object at all and no parts were found, so assume the upload has not started yet or was aborted.
                    yield 0;
                } else {
                    // Could not find upload and found the object, so the upload must have been completed.
                    yield 100;
                }
            } elseif ($response->getStatusCode() === 200) {
                $contents = $response->getDecodedBody();
                $parts = $contents['Part'] ?? [];
                if (!empty($parts['PartNumber'])) {
                    $uploadedPartsCount = 1;
                } else {
                    $uploadedPartsCount = count($parts);
                }
                yield ($uploadedPartsCount / $partsCount) * 100;
            } else {
                $response->assertIsOk();
            }
        });
    }

    /**
     * Generator for constantly retrieving the upload progress of a MediaClip being uploaded.
     * Note that this function does not have an poll amount limit and will continue indefinitely until the progress reaches 100%.
     * Also note that this is method is NOT asynchronous.
     *
     * Example:
     * ```php
     * foreach($sdk->mediaclip->uploadProgressGenerator(...) as $uploadProgress)
     * {
     *      print("Upload progress of mediaclip: $uploadProgress%");
     * }
     * ```
     *
     * @param string|Uri $listPartsUrl The URL for retrieving parts of the MediaClip file that were uploaded.
     * @param string|Uri $headObjectUrl The URL for retrieving metadata of the MediaClip file that was uploaded.
     * @param int $partsCount The total number of parts that are being uploaded.
     * @param int $pollInterval The minimum interval time in milliseconds between each upload progress poll, defaults to 2 seconds.
     *
     * @throws \BlueBillywig\Exception\HTTPRequestException
     */
    public function uploadProgressGenerator(string|Uri $listPartsUrl, string|Uri $headObjectUrl, int $partsCount, int $pollInterval = self::DEFAULT_UPLOAD_PROGRESS_POLL_INTERVAL): \Generator
    {
        $uploadProgress = $timeStart = $timePrevStart = 0;
        while ($uploadProgress !== 100) {
            $timeStart = floor(microtime(true) * 1000);
            $delayTime = $pollInterval - ($timeStart - $timePrevStart);
            if ($delayTime < 0) {
                $delayTime = 0;
            }
            $uploadProgress = $this->getUploadProgress($listPartsUrl, $headObjectUrl, $partsCount, $delayTime);
            yield $uploadProgress;
            $timePrevStart = $timeStart;
        }
    }

    /**
     * Retrieve the path to the source file of a MediaClip and return a promise.
     *
     * @param int|string $mediaClipId The ID of the MediaClip.
     * @param bool $absolute Whether the returned path should be absolute, defaults to true.
     */
    public function getSourcePathAsync(int|string $mediaClipId, bool $absolute = true): PromiseInterface
    {
        return Coroutine::of(function () use ($mediaClipId, $absolute) {
            $response = (yield $this->entity->getAsync($mediaClipId));
            $response->assertIsOk();
            $mediaClipData = $response->getDecodedBody();
            $mediaClipSrc = $mediaClipData['src'];
            if (!$absolute) {
                yield $mediaClipSrc;
            } else {
                $absoluteVideoPath = (yield $this->getAbsoluteVideoPathAsync($mediaClipSrc));
                yield $absoluteVideoPath;
            }
        });
    }

    /**
     * Parse a relative video path to an absolute one and return a promise.
     *
     * @param string $relativeVideoPath The relative video path to be parsed to an absolute one.
     */
    public function getAbsoluteVideoPathAsync(string $relativeVideoPath): PromiseInterface
    {
        return Coroutine::of(function () use ($relativeVideoPath) {
            $publicationData = (yield $this->sdk->getPublicationDataAsync());
            $dmap = $publicationData["defaultMediaAssetPath"];
            $relativeVideoPath = ltrim($relativeVideoPath, '/');
            yield "$dmap/$relativeVideoPath";
        });
    }
}
