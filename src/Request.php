<?php

namespace BlueBillywig;

use GuzzleHttp\Psr7\Request as GuzzleHttpRequest;

class Request extends GuzzleHttpRequest
{
    /**
     * Retrieve the query parameters as a list.
     *
     * @return array[]
     */
    public function getQueryParams(): array
    {
        parse_str($this->getUri()->getQuery(), $output);
        return $output;
    }

    /**
     * Retrieve a single query parameter by its name if it exists.
     *
     * @param string $queryParam The name of the query parameter.
     */
    public function getQueryParam(string $queryParam): ?string
    {
        $queryParams = $this->getQueryParams();
        return $queryParams[$queryParam] ?? null;
    }
}
