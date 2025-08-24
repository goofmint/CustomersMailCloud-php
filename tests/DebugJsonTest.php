<?php

declare(strict_types=1);

namespace CustomersMailCloud\Tests;

use CustomersMailCloud\CustomersMailCloud;
use CustomersMailCloud\EmailAddress;
use PHPUnit\Framework\TestCase;

/**
 * Debug test to verify JSON conversion
 */
class DebugJsonTest extends TestCase
{
    private CustomersMailCloud $client;
    private array $testConfig;

    protected function setUp(): void
    {
        // Load environment variables
        $envFile = __DIR__ . '/../.env';
        if (!file_exists($envFile)) {
            $this->markTestSkipped('.env file not found');
        }

        $env = parse_ini_file($envFile);
        if (!$env || !isset($env['API_USER']) || !isset($env['API_KEY'])) {
            $this->markTestSkipped('API_USER and API_KEY not found in .env file');
        }

        $this->client = new CustomersMailCloud($env['API_USER'], $env['API_KEY']);
        
        $this->testConfig = [
            'to' => $env['TEST_TO'] ?? '',
            'to_name' => $env['TEST_NAME'] ?? '',
            'from' => $env['TEST_FROM'] ?? '',
            'from_name' => $env['TEST_FROM_NAME'] ?? '',
        ];
    }

    public function testEmailAddressToJson(): void
    {
        echo "\n=== Testing EmailAddress to_json() ===\n";
        
        $emailAddress = new EmailAddress('test@example.com', 'User', [
            'key1' => 'value1',
            'key2' => 'value2',
            'product_name' => 'Premium Plan',
            'order_id' => 'ORDER-12345'
        ]);
        
        echo "EmailAddress properties:\n";
        echo "  address: " . $emailAddress->address . "\n";
        echo "  name: " . $emailAddress->name . "\n";
        echo "  substitutions: " . print_r($emailAddress->substitutions, true) . "\n";
        
        $json = $emailAddress->to_json();
        echo "JSON output: " . $json . "\n";
        
        $decoded = json_decode($json, true);
        echo "Decoded array:\n";
        print_r($decoded);
        
        $this->assertIsArray($decoded);
        $this->assertEquals('test@example.com', $decoded['address']);
        $this->assertEquals('User', $decoded['name']);
        $this->assertEquals('value1', $decoded['key1']);
        $this->assertEquals('value2', $decoded['key2']);
    }

    public function testTransactionEmailDataPreparation(): void
    {
        if (empty($this->testConfig['to']) || empty($this->testConfig['from'])) {
            $this->markTestSkipped('TEST_TO and TEST_FROM are required');
        }

        echo "\n=== Testing TransactionEmail data preparation ===\n";
        
        $email = $this->client->transaction_email();
        
        // Add recipient with substitutions
        $email->add_to($this->testConfig['to'], $this->testConfig['to_name'], [
            'product_name' => 'Test Product',
            'order_id' => 'DEBUG-123',
            'customer_name' => 'Debug User'
        ]);
        
        $email->set_from($this->testConfig['from'], $this->testConfig['from_name']);
        
        $email->subject = 'Debug Test: ((#product_name#))';
        $email->text = 'Hello ((#customer_name#)), your order ((#order_id#)) is ready.';
        $email->html = '<p>Hello <strong>((#customer_name#))</strong>, your order <em>((#order_id#))</em> is ready.</p>';
        
        echo "Email object state:\n";
        echo "TO recipients count: " . count($email->to) . "\n";
        
        foreach ($email->to as $index => $recipient) {
            echo "Recipient $index:\n";
            echo "  JSON: " . $recipient->to_json() . "\n";
            
            $decoded = json_decode($recipient->to_json(), true);
            echo "  Decoded:\n";
            foreach ($decoded as $key => $value) {
                echo "    $key: $value\n";
            }
        }
        
        echo "FROM: " . ($email->from ? $email->from->to_json() : 'null') . "\n";
        echo "Subject: " . $email->subject . "\n";
        echo "Text: " . $email->text . "\n";
        
        // This will output the debug information from send() method
        try {
            $result = $email->send();
            echo "Send result: " . ($result ? 'true' : 'false') . "\n";
        } catch (\Exception $e) {
            echo "Send error: " . $e->getMessage() . "\n";
        }
    }
    
    public function testJsonConversionProcess(): void
    {
        echo "\n=== Testing JSON conversion step by step ===\n";
        
        // Step 1: Create EmailAddress
        $emailAddr = new EmailAddress('test@example.com', 'Test User', [
            'var1' => 'value1',
            'var2' => 'value2'
        ]);
        
        echo "Step 1 - EmailAddress created\n";
        echo "Raw JSON: " . $emailAddr->to_json() . "\n";
        
        // Step 2: Convert to array for 'to' field
        $toArray = [];
        $toArray[] = json_decode($emailAddr->to_json(), true);
        
        echo "\nStep 2 - Converted to array element\n";
        print_r($toArray);
        
        // Step 3: JSON encode the array
        $toJson = json_encode($toArray);
        echo "\nStep 3 - Final 'to' JSON for API\n";
        echo $toJson . "\n";
        
        // This should match what gets sent in the 'to' field
        $this->assertIsString($toJson);
        $this->assertStringContainsString('"address":"test@example.com"', $toJson);
        $this->assertStringContainsString('"name":"Test User"', $toJson);
        $this->assertStringContainsString('"var1":"value1"', $toJson);
        $this->assertStringContainsString('"var2":"value2"', $toJson);
    }
}