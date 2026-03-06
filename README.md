# Blue Billywig SAPI PHP SDK

This PHP SDK provides abstractions to interact with the Blue Billywig Server API.

## Requirements

- PHP >= 8.1

## Installation

```console
composer require bluebillywig/bb-sapi-php-sdk
```

## Quick Start

```php
use BlueBillywig\Sdk;

$sdk = Sdk::withRPCTokenAuthentication(
    'my-publication',
    1,                // token ID
    'shared-secret'   // shared secret
);

// List media clips
$response = $sdk->mediaclip->list();
$data = $response->getDecodedBody();
print_r($data);
```

## Authentication

The SDK uses HOTP-based RPC token authentication. You need a **token ID** and **shared secret** from your Blue Billywig publication settings.

```php
use BlueBillywig\Sdk;
use BlueBillywig\Authentication\RPCTokenAuthenticator;

// Recommended: use the convenience factory
$sdk = Sdk::withRPCTokenAuthentication('my-publication', $tokenId, $sharedSecret);

// Or provide a custom authenticator
$authenticator = new RPCTokenAuthenticator($tokenId, $sharedSecret);
$sdk = new Sdk('my-publication', $authenticator);
```

**Clock synchronization**: The RPC token is time-based (HOTP). Both client and server clocks must be reasonably synchronized (within the token expiration window, default 120 seconds). Significant clock drift will cause authentication failures.

## Entities

All entities support standard CRUD operations where applicable:

| Entity | List | Get | Create | Update | Delete |
|---|---|---|---|---|---|
| `$sdk->mediaclip` | Yes | Yes | Yes | Yes | Yes |
| `$sdk->playlist` | Yes | Yes | Yes | Yes | Yes |
| `$sdk->channel` | Yes | Yes | Yes | Yes | Yes |
| `$sdk->playout` | Yes | Yes | Yes | Yes | Yes |
| `$sdk->subtitle` | Yes | Yes | Yes | Yes | Yes |
| `$sdk->thumbnail` | - | - | - | - | - |

### Sync and Async

Every entity method is available in both synchronous and asynchronous variants. Async methods return a `GuzzleHttp\Promise\PromiseInterface`.

```php
// Synchronous
$response = $sdk->mediaclip->list();

// Asynchronous
$promise = $sdk->mediaclip->listAsync();
$response = $promise->wait();
```

### Media Clips

```php
// List
$response = $sdk->mediaclip->list(15, 0, 'createddate desc');

// Get (with optional language and job inclusion)
$response = $sdk->mediaclip->get(123);
$response = $sdk->mediaclip->get(123, 'en', false);

// Create
$response = $sdk->mediaclip->create(['title' => 'My Video']);

// Update
$response = $sdk->mediaclip->update(123, ['title' => 'Updated Title']);

// Delete (with optional purge)
$response = $sdk->mediaclip->delete(123);
$response = $sdk->mediaclip->delete(123, true); // purge
```

### Playlists, Channels, Playouts, Subtitles

```php
// All follow the same pattern
$response = $sdk->playlist->list();
$response = $sdk->playlist->get(1);
$response = $sdk->playlist->create(['title' => 'My Playlist']);
$response = $sdk->playlist->update(1, ['title' => 'Updated']);
$response = $sdk->playlist->delete(1);
```

## File Uploads

The SDK supports single-chunk and multi-part uploads to S3 via presigned URLs.

```php
// 1. Initialize the upload
$initResponse = $sdk->mediaclip->initializeUpload('/path/to/video.mp4');
$initResponse->assertIsOk();
$uploadData = $initResponse->getDecodedBody();

// 2. Execute the upload
$success = $sdk->mediaclip->helper->executeUpload('/path/to/video.mp4', $uploadData);

// 3. (Optional) Track upload progress
foreach ($sdk->mediaclip->helper->uploadProgressGenerator(
    $uploadData['listPartsUrl'],
    $uploadData['headObjectUrl'],
    $uploadData['chunks'],
) as $progress) {
    echo "Upload progress: {$progress}%\n";
}
```

Async upload using coroutines:

```php
use GuzzleHttp\Promise\Coroutine;

$promise = Coroutine::of(function () use ($sdk, $mediaClipPath) {
    $response = (yield $sdk->mediaclip->initializeUploadAsync($mediaClipPath));
    $response->assertIsOk();

    yield $sdk->mediaclip->helper->executeUploadAsync($mediaClipPath, $response->getDecodedBody());
});
$promise->wait();
```

## Thumbnails

```php
// Generate an absolute thumbnail URL with dimensions
$url = $sdk->thumbnail->helper->getAbsoluteImagePath('/path/to/image.jpg', 640, 360);
// => https://my-publication.bbvms.com/image/640/360/path/to/image.jpg
```

## Response Handling

All entity methods return a `SapiResponse` object:

```php
$response = $sdk->mediaclip->get(123);

// Check status
if ($response->isOk()) {
    $data = $response->getDecodedBody();
}

// Or assert (throws on non-2xx)
$response->assertIsOk();
$data = $response->getDecodedBody();

// Access response details
$response->getStatusCode();            // e.g. 200
$response->getBody()->getContents();   // raw body string
$response->getJsonBody();              // parse as JSON
$response->getXmlBody();               // parse as XML
$response->getDecodedBody();           // try JSON first, then XML
$response->getStatusCodeCategory();    // HTTPStatusCodeCategory enum
$response->getRequest();               // the original Request object

// Batch response utilities
Response::allOk($responses);           // true if all 2xx
Response::assertAllOk($responses);     // throws on first non-2xx
Response::getFailedResponses($responses); // generator yielding non-2xx responses
```

## Error Handling

The SDK throws typed exceptions for HTTP errors:

```php
use BlueBillywig\Exception\HTTPRequestException;
use BlueBillywig\Exception\HTTPClientErrorRequestException;
use BlueBillywig\Exception\HTTPServerErrorRequestException;

try {
    $response = $sdk->mediaclip->get(999);
    $response->assertIsOk();
} catch (HTTPClientErrorRequestException $e) {
    // 4xx error
    echo "Client error {$e->getCode()}: {$e->getMessage()}\n";
    echo "Response body: {$e->getResponseBody()}\n";
} catch (HTTPServerErrorRequestException $e) {
    // 5xx error
    echo "Server error {$e->getCode()}: {$e->getMessage()}\n";
}
```

## Configuration

```php
$sdk = Sdk::withRPCTokenAuthentication('my-publication', $tokenId, $sharedSecret, [
    // Pass any Guzzle client options
    // See: https://docs.guzzlephp.org/en/stable/request-options.html
]);
```

## Development

```bash
# Install dependencies
composer install

# Lint
composer run lint

# Run tests
composer run test:unit

# Run tests with coverage
composer run test:unit:coverage
```
