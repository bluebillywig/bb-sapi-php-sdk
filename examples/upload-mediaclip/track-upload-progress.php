<?php

require_once(__DIR__ . '/../../vendor/autoload.php');

use BlueBillywig\Sdk;

$publication = "my-publication"; // The publication name (https://<publication name>.bbvms.com) in which the account and API key were created.
$tokenId = 1; // The ID of the generated API key.
$sharedSecret = "my-shared-secret"; // The randomly generated shared secret.

$sdk = Sdk::withRPCTokenAuthentication($publication, $tokenId, $sharedSecret);

$mediaClipPath = "/path/to/a/mediaclip.mp4";

$response = $sdk->mediaclip->initializeUpload($mediaClipPath);
$response->assertIsOk();
$responseContent = $response->getDecodedBody();

$uploadProgress = $sdk->mediaclip->helper->getUploadProgress($responseContent['listPartsUrl'], $responseContent['headObjectUrl'], $responseContent['chunks']);

print("Mediaclip upload progress: $uploadProgress%");

$sdk->mediaclip->helper->executeUpload($mediaClipPath, $responseContent);

$uploadProgress = $sdk->mediaclip->helper->getUploadProgress($responseContent['listPartsUrl'], $responseContent['headObjectUrl'], $responseContent['chunks']);

print("Mediaclip upload progress: $uploadProgress%");
