<?php

namespace BlueBillywig;

use BlueBillywig\Exception\HTTPClientErrorRequestException;
use BlueBillywig\Exception\HTTPRequestException;
use BlueBillywig\Exception\HTTPServerErrorRequestException;
use BlueBillywig\Util\HTTPStatusCodeCategory;
use GuzzleHttp\Psr7\Response as GuzzleHttpResponse;

class Response extends GuzzleHttpResponse
{
    public function __construct(
        private readonly Request $request,
        int $status = 200,
        array $headers = [],
        $body = null,
        string $version = '1.1',
        ?string $reason = null
    ) {
        parent::__construct($status, $headers, $body, $version, $reason);
    }

    /**
     * Retrieve whether the StatusCode is in the range of 200 to 299.
     */
    public function isOk(): bool
    {
        return $this->getStatusCodeCategory() === HTTPStatusCodeCategory::Successful;
    }

    /**
     * Check if the StatusCode is in the range of 200 to 299 and throw an exception if it is not.
     */
    public function assertIsOk(): void
    {
        if (!$this->isOk()) {
            $body = $this->getBody();
            $responseBody = $body->getSize() > 0 ? $body->getContents() : null;
            switch ($this->getStatusCodeCategory()) {
                case HTTPStatusCodeCategory::ClientError:
                    throw new HTTPClientErrorRequestException($this->getReasonPhrase(), $this->getStatusCode(), null, $responseBody);
                case HTTPStatusCodeCategory::ServerError:
                    throw new HTTPServerErrorRequestException($this->getReasonPhrase(), $this->getStatusCode(), null, $responseBody);
                default:
                    throw new HTTPRequestException($this->getReasonPhrase(), $this->getStatusCode(), null, $responseBody);
            }
        }
    }

    /**
     * Retrieve the StatusCode category.
     */
    public function getStatusCodeCategory(): HTTPStatusCodeCategory
    {
        $statusCode = $this->getStatusCode();
        return match (true) {
            $statusCode >= 100 && $statusCode <= 199 => HTTPStatusCodeCategory::Informational,
            $statusCode >= 200 && $statusCode <= 299 => HTTPStatusCodeCategory::Successful,
            $statusCode >= 300 && $statusCode <= 399 => HTTPStatusCodeCategory::Redirection,
            $statusCode >= 400 && $statusCode <= 499 => HTTPStatusCodeCategory::ClientError,
            $statusCode >= 500 && $statusCode <= 599 => HTTPStatusCodeCategory::ServerError,
            default => throw new \UnexpectedValueException("Unexpected HTTP status code: {$statusCode}"),
        };
    }

    /**
     * Retrieve whether the StatusCodes of a list of Responses are in the range of 200 to 299.
     *
     * @param Response[] $responseList The list of Responses for which to check the status code.
     */
    public static function allOk(array $responseList): bool
    {
        foreach ($responseList as $response) {
            if (!$response->isOk()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if the StatusCodes of a list of Responses are in the range of 200 to 299 and throw an exception if at least one is not.
     */
    public static function assertAllOk(array $responseList): void
    {
        foreach ($responseList as $response) {
            $response->assertIsOk();
        }
    }

    /**
     * Retrieve the failed Responses of a list of Responses.
     */
    public static function getFailedResponses(array $responseList): \Generator
    {
        foreach ($responseList as $response) {
            if (!$response->isOk()) {
                yield $response;
            }
        }
    }

    /**
     * Retrieve the Request object of this Response.
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Get the Response Body as JSON.
     *
     * @param bool $associative When **TRUE**, returned objects will be converted into associative arrays.
     *
     * @throws \UnexpectedValueException
     * @throws \JsonException
     */
    public function getJsonBody(bool $associative = true): null|array|object
    {
        $body = $this->getBody();
        if ($body->getSize() === 0) {
            return null;
        }
        return json_decode($body->getContents(), $associative, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Get the Response Body as XML.
     *
     * @param bool $associative When **TRUE**, returned objects will be converted into associative arrays.
     *
     * @throws \UnexpectedValueException
     */
    public function getXmlBody(bool $associative = true): null|array|\SimpleXMLElement
    {
        $body = $this->getBody();
        if ($body->getSize() === 0) {
            return null;
        }
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body->getContents(), \SimpleXMLElement::class, LIBXML_NONET);
        if (empty($xml)) {
            $error = libxml_get_last_error();
            throw new \UnexpectedValueException(
                $error !== false ? $error->message : 'Failed to parse XML',
                $error !== false ? $error->level : 0
            );
        } elseif (!$associative) {
            return $xml;
        }
        return json_decode(json_encode($xml), true);
    }

    /**
     * Get the decoded Response Body
     * This function attempts to decode the body as JSON first and then as XML if that fails.
     *
     * @param bool $associative When **TRUE**, returned objects will be converted into associative arrays.
     *
     * @throws \RuntimeException
     */
    public function getDecodedBody(bool $associative = true): null|array|object
    {
        try {
            return $this->getJsonBody($associative);
        } catch (\JsonException) {
            $this->getBody()->rewind();
            try {
                return $this->getXmlBody($associative);
            } catch (\UnexpectedValueException) {
                throw new \RuntimeException("Could not load body as JSON or XML.");
            }
        }
    }
}
