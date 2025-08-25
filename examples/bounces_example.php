<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CustomersMailCloud\CustomersMailCloud;
use CustomersMailCloud\CustomersMailCloudError;
use CustomersMailCloud\Bounce;

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

echo "=== CustomersMailCloud Bounces API Example ===\n\n";

try {
    // Example 1: Basic bounces list for last 7 days
    echo "1. Getting bounces list for the last 7 days:\n";
    
    $params = [
        'server_composition' => 'sandbox', // Adjust based on your server composition
        'start_date' => date('Y-m-d', strtotime('-7 days')), // 7 days ago
        'end_date' => date('Y-m-d', strtotime('-1 day')), // Yesterday
        'r' => 10 // Limit to 10 records
    ];
    
    $bounces = $client->bounces($params);
    
    echo "Found " . count($bounces) . " bounce records\n";
    
    if (!empty($bounces)) {
        echo "\nSample bounce record:\n";
        $sample = $bounces[0];
        echo "  created: {$sample->created}\n";
        echo "  status: {$sample->status}\n";
        echo "  reason: {$sample->reason}\n";
        echo "  from: {$sample->from}\n";
        echo "  to: {$sample->to}\n";
        echo "  messageId: {$sample->messageId}\n";
        echo "  subject: {$sample->subject}\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
    
    // Example 2: Filter by bounce status (User unknown)
    echo "2. Getting user unknown bounces (status=2):\n";
    
    $userUnknownBounces = $client->bounces([
        'server_composition' => 'sandbox',
        'start_date' => date('Y-m-d', strtotime('-7 days')),
        'end_date' => date('Y-m-d', strtotime('-1 day')),
        'status' => 2, // User unknown
        'r' => 5
    ]);
    
    echo "Found " . count($userUnknownBounces) . " user unknown bounce records\n";
    
    foreach ($userUnknownBounces as $bounce) {
        echo "  Status: " . ($bounce->status ?: 'N/A') . 
             " | Reason: " . ($bounce->reason ?: 'N/A') . 
             " | To: " . ($bounce->to ?: 'N/A') . "\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
    
    // Example 3: Filter by from address
    echo "3. Getting bounces from info@dxlabo.com:\n";
    
    $fromBounces = $client->bounces([
        'server_composition' => 'sandbox',
        'start_date' => date('Y-m-d', strtotime('-7 days')),
        'end_date' => date('Y-m-d', strtotime('-1 day')),
        'from' => 'info@dxlabo.com',
        'r' => 5
    ]);
    
    echo "Found " . count($fromBounces) . " bounce records from info@dxlabo.com\n";
    
    echo "\n" . str_repeat("-", 50) . "\n\n";

    // Example 4: With search options for exact match
    echo "4. Using exact match search:\n";
    
    $exactMatchBounces = $client->bounces([
        'server_composition' => 'sandbox',
        'start_date' => date('Y-m-d', strtotime('-7 days')),
        'end_date' => date('Y-m-d', strtotime('-1 day')),
        'from' => 'info@dxlabo.com',
        'search_option' => ['from' => 'full'], // Exact match
        'r' => 5
    ]);
    
    echo "Found " . count($exactMatchBounces) . " exact match bounces\n";
    
    echo "\n=== Bounce Status Types ===\n";
    echo "Status 1: Host unknown\n";
    echo "Status 2: User unknown\n";
    echo "Status 3: Retry timeout\n";
    echo "Status 4: Receive rejection\n";
    echo "Status 5: Capacity over\n";
    echo "Status 6: Transfer error\n";
    echo "Status 7: Receive server error\n";
    echo "Status 8: Size over\n";
    echo "Status 9: Address format error\n";
    echo "Status 10: Delivery stop address\n";
    echo "Status 11: Unsubscribe\n";
    echo "Status 99: Other error\n";
    
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