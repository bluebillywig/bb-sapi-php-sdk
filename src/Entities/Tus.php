<?php

namespace BlueBillywig\Entities;

use BlueBillywig\Entity;
use BlueBillywig\Request;
use BlueBillywig\Response;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;

/**
 * TUS resumable upload entity for the Blue Billywig SAPI.
 *
 * Implements the TUS protocol coordination layer:
 * - TUS tracks upload state and resumability via SAPI
 * - Actual file data is uploaded directly to S3 using presigned URLs
 * - No file data passes through the PHP server
 *
 * Flow:
 * 1. initUpload() → POST /sapi/tus → returns uploadId + presigned URLs
 * 2. Client uploads parts directly to S3 using presigned URLs
 * 3. getStatus() → HEAD /sapi/tus/{id} → returns offset + remaining URLs
 * 4. complete() → POST /sapi/tus/{id}/complete → finalizes S3 multipart upload
 *
 * @method Response initUpload(string $filename, int $filesize, ?int $clipId = null, array $metadata = []) Initialize a TUS upload. @see initUploadAsync
 * @method Response getStatus(string $uploadId) Get upload status and offset. @see getStatusAsync
 * @method Response complete(string $uploadId, array $parts = []) Complete the upload. @see completeAsync
 * @method Response abort(string $uploadId) Abort the upload. @see abortAsync
 * @method Response signPart(string $uploadId, int $partNumber) Get a presigned URL for a specific part. @see signPartAsync
 */
class Tus extends Entity
{
    const TUS_VERSION = '1.0.0';

    /**
     * Initialize a TUS resumable upload and return a promise.
     *
     * Creates a new upload on the server which returns:
     * - An upload ID for tracking
     * - Presigned S3 URLs for direct chunk uploads
     * - Part size configuration
     *
     * @param string $filename  Original filename.
     * @param int    $filesize  Total file size in bytes.
     * @param ?int   $clipId    Optional existing clip ID to upload to.
     * @param array  $metadata  Additional metadata (title, description, etc.).
     *
     * @return PromiseInterface Resolves to Response with uploadId and presigned URLs.
     */
    public function initUploadAsync(
        string $filename,
        int $filesize,
        ?int $clipId = null,
        array $metadata = []
    ): PromiseInterface {
        $tusMetadata = array_merge(
            ['filename' => $filename],
            $metadata
        );

        if ($clipId) {
            $tusMetadata['clipId'] = (string) $clipId;
        }

        $headers = [
            'Upload-Length'   => (string) $filesize,
            'Upload-Metadata' => self::encodeTusMetadata($tusMetadata),
            'Tus-Resumable'  => self::TUS_VERSION,
            'Content-Type'   => 'application/offset+octet-stream',
        ];

        return $this->sdk->sendRequestAsync(
            new Request('POST', '/sapi/tus', '', $headers)
        );
    }

    /**
     * Get the current upload status (offset and remaining presigned URLs).
     *
     * @param string $uploadId The upload ID returned by initUpload.
     *
     * @return PromiseInterface Resolves to Response with offset and presigned URLs.
     */
    public function getStatusAsync(string $uploadId): PromiseInterface
    {
        return $this->sdk->sendRequestAsync(
            new Request('HEAD', '/sapi/tus/' . urlencode($uploadId), '', [
                'Tus-Resumable' => self::TUS_VERSION,
            ])
        );
    }

    /**
     * Complete the S3 multipart upload.
     *
     * Call this after all parts have been uploaded to S3 via presigned URLs.
     *
     * @param string  $uploadId The upload ID.
     * @param array[] $parts    Array of completed parts with ETag and PartNumber.
     *                          Format: [['ETag' => '"abc..."', 'PartNumber' => 1], ...]
     *
     * @return PromiseInterface Resolves to Response confirming completion.
     */
    public function completeAsync(string $uploadId, array $parts = []): PromiseInterface
    {
        return $this->sdk->sendRequestAsync(
            new Request('POST', '/sapi/tus/' . urlencode($uploadId) . '/complete'),
            [
                RequestOptions::JSON => ['parts' => $parts],
            ]
        );
    }

    /**
     * Abort (terminate) an in-progress upload.
     *
     * Cleans up the S3 multipart upload and removes tracking state.
     *
     * @param string $uploadId The upload ID to abort.
     *
     * @return PromiseInterface Resolves to Response confirming termination.
     */
    public function abortAsync(string $uploadId): PromiseInterface
    {
        return $this->sdk->sendRequestAsync(
            new Request('DELETE', '/sapi/tus/' . urlencode($uploadId), '', [
                'Tus-Resumable' => self::TUS_VERSION,
            ])
        );
    }

    /**
     * Get a presigned URL for a specific part.
     *
     * Use this to get a fresh presigned URL for a part that needs to be
     * re-uploaded (e.g., after a failed upload or expired URL).
     *
     * @param string $uploadId   The upload ID.
     * @param int    $partNumber The part number (1-based).
     *
     * @return PromiseInterface Resolves to Response with presigned URL.
     */
    public function signPartAsync(string $uploadId, int $partNumber): PromiseInterface
    {
        return $this->sdk->sendRequestAsync(
            new Request('GET', '/sapi/tus/' . urlencode($uploadId) . '/sign/' . $partNumber)
        );
    }

    /**
     * Encode key-value pairs into TUS Upload-Metadata header format.
     *
     * @param array<string, string> $metadata Key-value pairs.
     * @return string Encoded header value (key base64value,key base64value,...).
     */
    private static function encodeTusMetadata(array $metadata): string
    {
        $parts = [];
        foreach ($metadata as $key => $value) {
            $parts[] = $key . ' ' . base64_encode($value);
        }
        return implode(',', $parts);
    }
}
