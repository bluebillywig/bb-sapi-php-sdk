<?php

namespace BlueBillywig;

use GuzzleHttp\Psr7\Request as GuzzleHttpRequest;

class Request extends GuzzleHttpRequest
{
    public function getQueryParams()
    {
        parse_str($this->getUri()->getQuery(), $output);
        return $output;
    }

    public function getQueryParam($queryParam)
    {
        $queryParams = $this->getQueryParams();
        return $queryParams[$queryParam] ?? null;
    }
}
