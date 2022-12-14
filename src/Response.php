<?php

namespace BlueBillywig;

use BlueBillywig\Exception\HTTPClientErrorRequestException;
use BlueBillywig\Exception\HTTPRequestException;
use BlueBillywig\Exception\HTTPServerErrorRequestException;
use GuzzleHttp\Psr7\Response as GuzzleHttpResponse;

enum HTTPStatusCodeCategory
{
    case Informational;
    case Successful;
    case Redirection;
    case ClientError;
    case ServerError;
}

class Response extends GuzzleHttpResponse
{
    private Request $request;

    public function __construct(
        Request $request,
        int $status = 200,
        array $headers = [],
        $body = null,
        string $version = '1.1',
        string $reason = null
    ) {
        parent::__construct($status, $headers, $body, $version, $reason);
        $this->request = $request;
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
            switch ($this->getStatusCodeCategory()) {
                case HTTPStatusCodeCategory::ClientError:
                    throw new HTTPClientErrorRequestException($this->getReasonPhrase(), $this->getStatusCode());
                case HTTPStatusCodeCategory::ServerError:
                    throw new HTTPServerErrorRequestException($this->getReasonPhrase(), $this->getStatusCode());
                default:
                    throw new HTTPRequestException($this->getReasonPhrase(), $this->getStatusCode());
            }
        }
    }

    /**
     * Retrieve the StatusCode category.
     */
    public function getStatusCodeCategory(): HTTPStatusCodeCategory
    {
        switch ($statusCode = $this->getStatusCode()) {
            case ($statusCode >= 100 && $statusCode <= 199):
                return HTTPStatusCodeCategory::Informational;
            case ($statusCode >= 200 && $statusCode <= 299):
                return HTTPStatusCodeCategory::Successful;
            case ($statusCode >= 300 && $statusCode <= 399):
                return HTTPStatusCodeCategory::Redirection;
            case ($statusCode >= 400 && $statusCode <= 499):
                return HTTPStatusCodeCategory::ClientError;
            case ($statusCode >= 500 && $statusCode <= 599):
                return HTTPStatusCodeCategory::ServerError;
            default:
                throw new \UnexpectedValueException("Invalid status code $statusCode.");
        }
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
    public static function getFailedResponse(array $responseList): \Generator
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
    public function getJson(bool $associative = true): null|array|object
    {
        $body = $this->getBody();
        if (!$body->isReadable()) {
            throw new \UnexpectedValueException("Response body is not readable.");
        } elseif ($body->getSize() === 0) {
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
     * @throws \LibXMLError
     */
    public function getXml(bool $associative = true): null|array|\SimpleXMLElement
    {
        $body = $this->getBody();
        if (!$body->isReadable()) {
            throw new \UnexpectedValueException("Response body is not readable.");
        } elseif ($body->getSize() === 0) {
            return null;
        }
        $xml = simplexml_load_string($body);
        if (empty($xml)) {
            $error = libxml_get_last_error();
            if (!empty($error)) {
                throw $error;
            }
            return null;
        } elseif (!$associative) {
            return $xml;
        }
        return json_decode(json_encode($xml), true);
    }
}
