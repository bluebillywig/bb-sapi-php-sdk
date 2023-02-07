<?php

require_once(__DIR__ . '/../../vendor/autoload.php');

use BlueBillywig\Sdk;

$publication = "my-publication"; // The publication name (https://<publication name>.bbvms.com) in which the account and API key were created.
$tokenId = 1; // The ID of the generated API key.
$sharedSecret = "my-shared-secret"; // The randomly generated shared secret.

$sdk = Sdk::withRPCTokenAuthentication($publication, $tokenId, $sharedSecret);

$mediaClipPath = "/path/to/a/mediaclip.mp4";

$response = $sdk->mediaclip->create([
    'title' => 'My MediaClip'
]);
$response->assertIsOk();
$mediaClipId = $response->getDecodedBody()['id'];

$response = $sdk->mediaclip->initializeUpload($mediaClipPath, $mediaClipId);
$response->assertIsOk();
$responseContent = $response->getDecodedBody();
$sdk->mediaclip->helper->executeUpload($mediaClipPath, $responseContent);
