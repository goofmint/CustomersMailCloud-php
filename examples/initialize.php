<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CustomersMailCloud\CustomersMailCloud;

// Basic initialization
$apiUser = 'your-api-user';
$apiKey = 'your-api-key';

$client = new CustomersMailCloud($apiUser, $apiKey);

// Initialize with additional configuration
$client = new CustomersMailCloud($apiUser, $apiKey, [
    'timeout' => 60,
    'verify_ssl' => true,
    'base_uri' => 'https://api.smtps.jp/v1/'
]);

// Access various resources
$email = $client->email();
$template = $client->template();
$suppression = $client->suppression();
$statistics = $client->statistics();

echo "CustomersMailCloud SDK initialized successfully!\n";