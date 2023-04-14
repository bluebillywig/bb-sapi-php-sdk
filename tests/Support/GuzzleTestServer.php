<?php

namespace BlueBillywig\Tests\Support;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Server\Server as GuzzleServer;

class GuzzleTestServer extends GuzzleServer
{
    /**
     * @var Client
     */
    private static $client;
    private static bool $started = false;

    public static function flush(): ResponseInterface
    {
        return self::getClient()->request('DELETE', 'guzzle-server/requests');
    }

    public static function enqueue($responses): void
    {
        $data = [];
        foreach ((array) $responses as $response) {
            if (!($response instanceof ResponseInterface)) {
                throw new \Exception('Invalid response given.');
            }
            $headers = \array_map(static function ($h) {
                return \implode(' ,', $h);
            }, $response->getHeaders());

            $data[] = [
                'status' => (string) $response->getStatusCode(),
                'reason' => $response->getReasonPhrase(),
                'headers' => $headers,
                'body' => \base64_encode((string) $response->getBody()),
            ];
        }

        self::getClient()->request('PUT', 'guzzle-server/responses', [
            'json' => $data,
        ]);
    }

    public static function enqueueRaw(
        $statusCode,
        $reasonPhrase,
        $headers,
        $body
    ): void {
        $data = [
            [
                'status' => (string) $statusCode,
                'reason' => $reasonPhrase,
                'headers' => $headers,
                'body' => \base64_encode((string) $body),
            ],
        ];

        self::getClient()->request('PUT', 'guzzle-server/responses', [
            'json' => $data,
        ]);
    }

    public static function received(): array
    {
        if (!self::$started) {
            return [];
        }

        $response = self::getClient()->request('GET', 'guzzle-server/requests');
        $data = \json_decode($response->getBody(), true);

        return \array_map(static function ($message) {
            $uri = $message['uri'];
            if (isset($message['query_string'])) {
                $uri .= '?' . $message['query_string'];
            }
            $response = new Psr7\Request(
                $message['http_method'],
                $uri,
                $message['headers'],
                $message['body'],
                $message['version']
            );
            return $response->withUri(
                $response
                    ->getUri()
                    ->withScheme('http')
                    ->withHost($response->getHeaderLine('host'))
            );
        }, $data);
    }

    public static function stop(): void
    {
        if (self::$started) {
            self::getClient()->request('DELETE', 'guzzle-server');
        }

        self::$started = false;
    }

    public static function wait($maxTries = 5, $timeout = 1): void
    {
        $tries = 0;
        while (!self::isListening() && ++$tries < $maxTries) {
            \sleep($timeout);
        }

        if (!self::isListening()) {
            throw new \RuntimeException('Unable to contact node.js server');
        }
    }

    public static function start($maxTries = 5, $timeout = 1): void
    {
        if (self::$started) {
            return;
        }

        if (!self::isListening()) {
            $reflector = new \ReflectionClass(GuzzleServer::class);
            $serverDir = \dirname($reflector->getFileName());
            $sysTmpDir = \sys_get_temp_dir();
            $rawLogFile = "$sysTmpDir/server.log";
            $logFile = realpath($rawLogFile);
            // Workaround for realpath returning false on Mac for \sys_get_temp_dir location.
            if (empty($logFile)) {
                $logFile = $rawLogFile;
            }
            $baseCmd =
                'node ' .
                realpath($serverDir . '/server.js') .
                ' ' .
                self::$port .
                ' >> ' .
                $logFile;

            if (self::isWindows()) {
                pclose(popen('start /B ' . $baseCmd, 'r'));
            } else {
                \exec($baseCmd . ' 2>&1 &');
            }
            self::wait($maxTries, $timeout);
        }

        self::$started = true;
    }

    private static function isWindows(): bool
    {
        return substr(php_uname(), 0, 7) == 'Windows';
    }

    private static function isListening(): bool
    {
        try {
            self::getClient()->request('GET', 'guzzle-server/perf', [
                'connect_timeout' => 5,
                'timeout' => 5,
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private static function getClient(): Client
    {
        if (!self::$client) {
            self::$client = new Client([
                'base_uri' => self::$url,
                'sync' => true,
            ]);
        }

        return self::$client;
    }
}
