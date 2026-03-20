<?php

namespace BlueBillywig\Exception;

class HTTPRequestException extends \Exception
{
    private readonly ?string $responseBody;

    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null, ?string $responseBody = null)
    {
        parent::__construct($message, $code, $previous);
        $this->responseBody = $responseBody;
    }

    /**
     * Retrieve the response body that was returned with this error, if available.
     */
    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }
}
