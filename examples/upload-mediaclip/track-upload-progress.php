<?php

require_once(__DIR__ . '/vendor/autoload.php');

use BlueBillywig\Sdk;

$publication = "my-publication"; // The publication name (https://<publication name>.bbvms.com) in which the account and API key were created.
$tokenId = 1; // The ID of the generated API key.
$sharedSecret = "my-shared-secret"; // The randomly generated shared secret.

$sdk = Sdk::withRPCTokenAuthentication($publication, $tokenId, $sharedSecret);

$response = $sdk->mediaclip->initializeUpload("/path/to/a/mediaclip.mp4");
$responseContent = $response->getJson();

$uploadProgress = $sdk->mediaclip->helper->getUploadProgress($responseContent['listPartsUrl'], $responseContent['headObjectUrl'], $responseContent['chunks']);

print("Mediaclip upload progress: $uploadProgress%");

$sdk->mediaclip->helper->executeUpload("/path/to/a/mediaclip.mp4", $responseContent);

$uploadProgress = $sdk->mediaclip->helper->getUploadProgress($responseContent['listPartsUrl'], $responseContent['headObjectUrl'], $responseContent['chunks']);

print("Mediaclip upload progress: $uploadProgress%");
