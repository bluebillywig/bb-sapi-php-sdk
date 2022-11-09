<?php

namespace BlueBillywig;

use BlueBillywig\Authentication\Authenticator;
use BlueBillywig\Authentication\RPCTokenAuthenticator;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack as GuzzleHandlerStack;
use GuzzleHttp\Handler\CurlHandler as GuzzleCurlHandler;
use GuzzleHttp\Middleware as GuzzleMiddleware;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;

/**
 * @property \BlueBillywig\Entities\MediaClip $mediaclip
 */
class Sdk extends EntityRegister
{
    protected static array $entitiesCls = [
        \BlueBillywig\Entities\MediaClip::class
    ];

    private GuzzleClient $guzzleClient;
    private string $publication;

    public function __construct(string $publication, Authenticator $authenticator, array $options = [])
    {
        parent::__construct();
        $this->publication = $publication;
        $handler = new GuzzleCurlHandler();
        $stack = GuzzleHandlerStack::create($handler);
        $stack->push(GuzzleMiddleware::mapRequest($authenticator));
        $this->guzzleClient = new GuzzleClient(['handler' => $stack] + $options);
    }

    /**
     * Create a new Sdk instance with RPCToken authentication.
     *
     * @param string $publication The name of the OVP publication (https://[publication name].bbvms.com).
     * @param int $tokenId The ID of the token.
     * @param string $sharedSecret The randomly generated shared secret.
     * @param array $options Client configuration settings.
     */
    public static function withRPCTokenAuthentication(string $publication, int $tokenId, string $sharedSecret, array $options = []): static
    {
        return new static($publication, new RPCTokenAuthenticator($tokenId, $sharedSecret), $options);
    }

    /**
     * Send an asynchronous request.
     *
     * @param Request $request The Request as an object to send.
     * @param array $options An array of Request options. @see \GuzzleHttp\RequestOptions
     *
     * @throws \BlueBillyWig\Exception\HTTPRequestException
     */
    public function sendRequestAsync(Request $request, array $options = []): PromiseInterface
    {
        $requestUri = $request->getUri();
        if (substr($requestUri, 0, 6) === '/sapi/') {
            $uri = (new Uri("https://{$this->publication}.bbvms.com/"))->withPath(strval($request->getUri()));
        } else {
            $uri = $requestUri;
        }

        $promise = $this->guzzleClient->sendAsync($request->withUri($uri), $options);

        return $promise->then(function (ResponseInterface $response) use ($request) {
            return static::parseResponse($request, $response);
        }, function (RequestException $e) use ($request) {
            return static::parseResponse($request, $e->getResponse());
        });
    }

    /**
     * Send a synchronous request.
     *
     * @param Request $request The Request as an object to send.
     * @param array $options An array of Request options. @see \GuzzleHttp\RequestOptions
     *
     * @throws \BlueBillyWig\Exception\HTTPRequestException
     */
    public function sendRequest(Request $request, array $options = []): Response
    {
        return $this->sendRequestAsync($request, $options)->wait();
    }

    /**
     * Parse the ResponseInterface to a \BlueBillywig\Response object.
     *
     * @param Request $request The Request that was sent.
     * @param ResponseInterface $response The ResponseInterface that needs to be parsed.
     */
    public static function parseResponse(Request $request, ResponseInterface $response): Response
    {
        return new Response(
            $request,
            $response->getStatusCode(),
            $response->getHeaders(),
            $response->getBody(),
            $response->getProtocolVersion(),
            $response->getReasonPhrase()
        );
    }

    /**
     * Get the active Sdk instance.
     */
    protected function getSdk(): Sdk
    {
        return $this;
    }
}
