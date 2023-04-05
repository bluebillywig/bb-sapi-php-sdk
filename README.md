# Blue Billywig SAPI PHP SDK

This PHP SDK provides abstractions to interact with the Blue Billywig Server API.

## Installation

Installation can be done through composer:
```console
composer require bluebillywig/bb-sapi-php-sdk
```

## Usage

In order to use this SDK, three things are prerequisite:

1. A publication is created and active in the Blue Billywig _Online Video Platform_ (OVP).
2. An account is created within the publication in the OVP.
3. An API Key was created using the account in the OVP.

Once the aforementioned prerequisites are in place the SDK can be used in any PHP script:

```php
<?php

use BlueBillywig\Sdk;
use GuzzleHttp\Promise\Coroutine;

$publication = "my-publication"; // The publication name (https://<publication name>.bbvms.com) in which the account and API key were created.
$tokenId = 1; // The ID of the generated API key.
$sharedSecret = "my-shared-secret"; // The randomly generated shared secret.

$sdk = Sdk::withRPCTokenAuthentication($publication, $tokenId, $sharedSecret);

$mediaClipPath = "/path/to/a/mediaclip.mp4";

// Asynchronous
$promise = Coroutine::of(function () use ($sdk) {
    $response = (yield $sdk->mediaclip->initializeUploadAsync($mediaClipPath));
    $response->assertIsOk();

    yield $sdk->mediaclip->helper->executeUploadAsync($mediaClipPath, $response->getDecodedBody());
});
$promise->wait();

// Synchronous
$response = $sdk->mediaclip->initializeUpload($mediaClipPath);
$response->assertIsOk();

$sdk->mediaclip->helper->executeUpload($mediaClipPath, $response->getDecodedBody());
```
