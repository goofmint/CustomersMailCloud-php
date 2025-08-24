<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CustomersMailCloud\CustomersMailCloud;

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

// Create email
$email = $client->transaction_email();

// Basic email configuration
$email->add_to('test@example.com', 'テストユーザー', [
          'product_name' => 'テスト商品',
          'order_id' => 'ORDER-12345'
      ])
      ->set_from('sender@example.com', '送信者')
      ->set_reply_to('reply@example.com', 'リプライ先');

$email->subject = '注文確認: ((#product_name#))';
$email->text = 'ご注文ありがとうございます。注文ID: ((#order_id#))';
$email->html = '<p>ご注文ありがとうございます。<br>注文ID: <strong>((#order_id#))</strong></p>';

// Optional configurations
$email->charset = 'UTF-8';
$email->headers = [
    'X-Priority' => '1',
    'X-Custom-Header' => 'CustomValue'
];

try {
    echo "Sending email...\n";
    $response = $email->send();
    
    echo "Email sent successfully!\n";
    echo "Response: " . json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
} catch (\Exception $e) {
    echo "Failed to send email: " . $e->getMessage() . "\n";
}