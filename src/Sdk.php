<?php

namespace BlueBillywig;

use BlueBillywig\Authentication\Authenticator;
use BlueBillywig\Authentication\RPCTokenAuthenticator;
use Composer\CaBundle\CaBundle;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack as GuzzleHandlerStack;
use GuzzleHttp\Handler\CurlHandler as GuzzleCurlHandler;
use GuzzleHttp\Middleware as GuzzleMiddleware;
use GuzzleHttp\Promise\Coroutine;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
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
        $this->guzzleClient = new GuzzleClient($options + [
            'handler' => $stack,
            RequestOptions::VERIFY => CaBundle::getSystemCaRootBundlePath()
        ]);
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
     * @throws \GuzzleHttp\Exception\RequestException
     */
    public function sendRequestAsync(Request $request, array $options = []): PromiseInterface
    {
        $requestUri = $request->getUri();
        if (substr($requestUri, 0, 6) === '/sapi/') {
            $uri = (new Uri("https://{$this->publication}.bbvms.com/"))->withPath(strval($request->getUri()));
        } else {
            $uri = $requestUri;
        }

        return Coroutine::of(function () use ($request, $uri, $options) {
            try {
                $response = (yield $this->guzzleClient->sendAsync($request->withUri($uri), $options));
                yield static::parseResponse($request, $response);
            } catch (RequestException $e) {
                $response = $e->getResponse();
                if (empty($response)) {
                    throw $e;
                }
                yield static::parseResponse($request, $response);
            }
        });
    }

    /**
     * Send a synchronous request.
     *
     * @param Request $request The Request as an object to send.
     * @param array $options An array of Request options. @see \GuzzleHttp\RequestOptions
     *
     * @throws \BlueBillyWig\Exception\HTTPRequestException
     * @throws \GuzzleHttp\Exception\RequestException
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
