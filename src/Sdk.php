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
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * @property-read \BlueBillywig\Entities\MediaClip $mediaclip
 */
class Sdk extends EntityRegister
{
    protected static array $entitiesCls = [
        ['mediaclip' => \BlueBillywig\Entities\MediaClip::class]
    ];

    private GuzzleClient $guzzleClient;
    private string $publication;

    public function __construct(string $publication, Authenticator $authenticator, array $options = [])
    {
        parent::__construct();
        $this->publication = $publication;
        $handler = $options['handler'] ?? new GuzzleCurlHandler();
        $stack = GuzzleHandlerStack::create($handler);
        $stack->push(GuzzleMiddleware::mapRequest($authenticator));
        $options['handler'] = $stack;
        $this->guzzleClient = new GuzzleClient($options + [
            RequestOptions::VERIFY => CaBundle::getSystemCaRootBundlePath(),
            'base_uri' => "https://{$this->publication}.bbvms.com"
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
        return Coroutine::of(function () use ($request, $options) {
            try {
                $response = (yield $this->guzzleClient->sendAsync($request, $options));
                yield static::parseResponse($request, $response);
            } catch (RequestException $e) {
                $response = $e->getResponse();
                if (empty($response)) {
                    // @codeCoverageIgnoreStart
                    throw $e;
                    // @codeCoverageIgnoreEnd
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
     * @param string $parsedResponseCls The class to which the original Response should be parsed.
     */
    public static function parseResponse(Request $request, ResponseInterface $response, string $parsedResponseCls = Response::class): Response
    {
        if (!is_a($parsedResponseCls, Response::class, true)) {
            throw new \TypeError("Given response class is not a subtype of " . Response::class . ".");
        }
        return new $parsedResponseCls(
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
