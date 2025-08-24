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
$testTo = $env['TEST_TO'] ?? '';
$testFrom = $env['TEST_FROM'] ?? '';

if (empty($apiUser) || empty($apiKey) || empty($testTo) || empty($testFrom)) {
    die("Required credentials or test addresses are missing\n");
}

// Initialize client
$client = new CustomersMailCloud($apiUser, $apiKey);

// Create email with attachments
$email = $client->transaction_email();

$email->add_to($testTo, 'テストユーザー', [
          'document_type' => 'ライセンス文書',
          'image_type' => 'テスト画像',
          'date' => date('Y年m月d日')
      ])
      ->set_from($testFrom, '送信者')
      ->set_reply_to($testFrom, 'リプライ先');

$email->subject = '添付ファイル付きメール: ((#document_type#))と((#image_type#))';
$email->text = "添付ファイル付きのテストメールです。\n\n添付ファイル:\n- ((#document_type#)): LICENSE\n- ((#image_type#)): test.png\n\n送信日: ((#date#))";
$email->html = '<h1>添付ファイル付きメール</h1><p>添付ファイル付きのテストメールです。</p><h2>添付ファイル:</h2><ul><li><strong>((#document_type#)):</strong> LICENSE</li><li><strong>((#image_type#)):</strong> test.png</li></ul><p><strong>送信日:</strong> ((#date#))</p>';

// Add attachments (assuming test files exist in tests directory)
$testImagePath = __DIR__ . '/../tests/test.png';
$testTextPath = __DIR__ . '/../tests/LICENSE';

if (file_exists($testImagePath) && file_exists($testTextPath)) {
    $email->attachments = [$testImagePath, $testTextPath];
    
    echo "Adding attachments:\n";
    echo "- " . basename($testImagePath) . " (" . filesize($testImagePath) . " bytes)\n";
    echo "- " . basename($testTextPath) . " (" . filesize($testTextPath) . " bytes)\n\n";
} else {
    die("Test attachment files not found in tests directory\n");
}

try {
    echo "Sending email with attachments...\n";
    $result = $email->send();
    
    if ($result) {
        echo "Email sent successfully!\n";
        echo "Message ID: " . $email->id . "\n";
        echo "Attachments sent: " . count($email->attachments) . "\n";
    }
    
} catch (\Exception $e) {
    echo "Failed to send email: " . $e->getMessage() . "\n";
}