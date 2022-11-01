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

    public function isOk()
    {
        return $this->getStatusCodeCategory() === HTTPStatusCodeCategory::Successful;
    }

    public function assertIsOk()
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

    public static function allOk(array $responseList)
    {
        foreach ($responseList as $response) {
            if (!$response->isOk()) {
                return false;
            }
        }
        return true;
    }

    public static function assertAllOk(array $responseList)
    {
        foreach ($responseList as $response) {
            $response->assertIsOk();
        }
    }

    public static function getFailedResponse(array $responseList)
    {
        foreach ($responseList as $response) {
            if (!$response->isOk()) {
                return $response;
            }
        }
        return null;
    }

    public function getRequest()
    {
        return $this->request;
    }
}
