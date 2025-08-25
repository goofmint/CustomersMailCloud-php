<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CustomersMailCloud\CustomersMailCloud;
use CustomersMailCloud\CustomersMailCloudError;

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    die(".env file not found\n");
}

$env = parse_ini_file($envFile);
if (!$env || !isset($env['API_USER']) || !isset($env['API_KEY'])) {
    die("API_USER and API_KEY not found in .env file\n");
}

$apiUser = $env['API_USER'];
$apiKey = $env['API_KEY'];

if (empty($apiUser) || empty($apiKey)) {
    die("API credentials are empty\n");
}

// Initialize client
$client = new CustomersMailCloud($apiUser, $apiKey);

echo "=== CustomersMailCloud Deliveries API Example ===\n\n";

try {
    // Example 1: Basic deliveries list
    echo "1. Getting deliveries list for yesterday:\n";
    
    $params = [
        'server_composition' => 'default', // Adjust based on your server composition
        'date' => date('Y-m-d', strtotime('-1 day')), // Yesterday
        'r' => 10 // Limit to 10 records
    ];
    
    $deliveries = $client->deliveries($params);
    
    echo "Found " . count($deliveries) . " delivery records\n";
    
    if (!empty($deliveries)) {
        echo "\nSample delivery record:\n";
        $sample = $deliveries[0];
        echo "  created: {$sample->created}\n";
        echo "  subject: {$sample->subject}\n";
        echo "  status: {$sample->status}\n";
        echo "  from: {$sample->from}\n";
        echo "  to: {$sample->to}\n";
        echo "  messageId: {$sample->messageId}\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
    
    // Example 2: Filter by from address
    echo "2. Getting deliveries from info@dxlabo.com:\n";
    
    $filteredDeliveries = $client->deliveries([
        'server_composition' => 'default',
        'date' => date('Y-m-d', strtotime('-1 day')),
        'from' => 'info@dxlabo.com',
        'r' => 5
    ]);
    
    echo "Found " . count($filteredDeliveries) . " delivery records from info@dxlabo.com\n";
    
    foreach ($filteredDeliveries as $delivery) {
        echo "  Subject: " . ($delivery->subject ?: 'N/A') . 
             " | Status: " . ($delivery->status ?: 'N/A') . 
             " | To: " . ($delivery->to ?: 'N/A') . "\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
    
    // Example 3: Filter by status
    echo "3. Getting successful deliveries:\n";
    
    $successfulDeliveries = $client->deliveries([
        'server_composition' => 'default',
        'date' => date('Y-m-d', strtotime('-1 day')),
        'status' => 'succeeded',
        'r' => 5
    ]);
    
    echo "Found " . count($successfulDeliveries) . " successful delivery records\n";
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
    
    // Example 4: With search options for exact match
    echo "4. Using exact match search:\n";
    
    $exactMatchDeliveries = $client->deliveries([
        'server_composition' => 'default',
        'date' => date('Y-m-d', strtotime('-1 day')),
        'from' => 'info@dxlabo.com',
        'search_option' => ['from' => 'full'], // Exact match
        'r' => 5
    ]);
    
    echo "Found " . count($exactMatchDeliveries) . " exact match deliveries\n";
    
    echo "\n=== Example completed successfully ===\n";
    
} catch (CustomersMailCloudError $e) {
    echo "API Error occurred:\n";
    echo "Error Message: " . $e->getMessage() . "\n";
    echo "Error Codes: " . implode(', ', $e->getAllErrorCodes()) . "\n";
    
    $errorInfo = $e->getErrorInfo();
    if (!empty($errorInfo['errors'])) {
        echo "\nDetailed errors:\n";
        foreach ($errorInfo['errors'] as $error) {
            echo "  - Code: " . ($error['code'] ?? 'N/A') . 
                 ", Field: " . ($error['field'] ?? 'N/A') . 
                 ", Message: " . ($error['message'] ?? 'N/A') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}