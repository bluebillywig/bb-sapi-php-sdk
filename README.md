# BB SAPI PHP SDK

This PHP SDK provides abstractions to interact with the BlueBillywig Simple API.

## Installation

TODO

## Usage

In order to use this SDK, three things are prerequisite:

1. A publication is created and active in BlueBillyig _Online Video Platform_ (OVP).
2. An account is created within the publication in the OVP.
3. An API Key was created using the account in the OVP.

Once the aforementioned prerequisites are in place the SDK can be used in any PHP script:

```php
<?php

use BlueBillywig\Sdk;

$publication = "my-publication"; // The publication name (https://<publication name>.bbvms.com) in which the account and API key were created.
$tokenId = 1; // The ID of the generated API key.
$sharedSecret = "my-shared-secret"; // The randomly generated shared secret.

$sdk = Sdk::withRPCTokenAuthentication($publication, $tokenId, $sharedSecret);

// Asynchronous
$promise = $sdk->mediaclip->fullUploadAsync("/path/to/a/mediaclip.mp4");
$response = $promise->wait();

// Synchronous
$response = $sdk->mediaclip->fullUpload("/path/to/a/mediaclip.mp4");

$response->assertIsOk();
```
