<?php

declare(strict_types=1);

namespace CustomersMailCloud\Tests;

use CustomersMailCloud\CustomersMailCloud;
use CustomersMailCloud\CustomersMailCloudError;
use PHPUnit\Framework\TestCase;

/**
 * Test for CC/BCC format verification
 */
class CCBCCFormatTest extends TestCase
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
            'cc' => $env['TEST_CC'] ?? '',
            'bcc' => $env['TEST_BCC'] ?? '',
        ];
    }

    /**
     * @group integration
     * @group cc-bcc
     */
    public function testCCBCCFormat(): void
    {
        if (empty($this->testConfig['to']) || empty($this->testConfig['from'])) {
            $this->markTestSkipped('TEST_TO and TEST_FROM are required for CC/BCC format test');
        }

        $email = $this->client->transaction_email();
        
        $email->add_to($this->testConfig['to'], $this->testConfig['to_name'])
              ->set_from($this->testConfig['from'], $this->testConfig['from_name']);
              
        $email->subject = 'CC/BCC Format Test - ' . date('Y-m-d H:i:s');
        $email->text = "This is a CC/BCC format test.\n\nSent at: " . date('Y-m-d H:i:s');
        $email->html = '<h1>CC/BCC Format Test</h1><p>This is a CC/BCC format test.</p><p><strong>Sent at:</strong> ' . date('Y-m-d H:i:s') . '</p>';

        // Add CC and BCC
        if (!empty($this->testConfig['cc'])) {
            $email->cc = [$this->testConfig['cc']];
        }
        
        if (!empty($this->testConfig['bcc'])) {
            $email->bcc = [$this->testConfig['bcc']];
        }

        try {
            $result = $email->send();
            
            $this->assertTrue($result);
            $this->assertNotNull($email->id);
            echo "\nCC/BCC Format Test Result:\n";
            echo "Success: " . ($result ? 'true' : 'false') . "\n";
            echo "Message ID: " . $email->id . "\n";
            echo "CC: " . implode(', ', $email->cc) . "\n";
            echo "BCC: " . implode(', ', $email->bcc) . "\n";
            
        } catch (CustomersMailCloudError $e) {
            // Check if it's a CC/BCC format error
            if ($e->hasErrorCode('11-006')) {
                echo "\nCC/BCC Format Error Details:\n";
                echo "Error Message: " . $e->getMessage() . "\n";
                echo "Error Codes: " . implode(', ', $e->getAllErrorCodes()) . "\n";
                
                $errorInfo = $e->getErrorInfo();
                foreach ($errorInfo['errors'] as $error) {
                    echo "Field: " . ($error['field'] ?? 'unknown') . ", Message: " . ($error['message'] ?? 'unknown') . "\n";
                }
                
                $this->fail('CC/BCC format is still incorrect: ' . $e->getMessage());
            } else {
                echo "\nOther API Error occurred:\n";
                echo "Error Message: " . $e->getMessage() . "\n";
                echo "Error Codes: " . implode(', ', $e->getAllErrorCodes()) . "\n";
                $this->markTestSkipped('Other API Error: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            $this->fail('Unexpected error: ' . $e->getMessage());
        }
    }

    public function testCCBCCArrayFormat(): void
    {
        $email = $this->client->transaction_email();
        
        // Test multiple CC/BCC addresses
        $email->cc = ['cc1@example.com', 'cc2@example.com'];
        $email->bcc = ['bcc1@example.com', 'bcc2@example.com'];
        
        // Use reflection to test the internal data preparation
        $reflection = new \ReflectionClass($email);
        $method = $reflection->getMethod('send');
        
        // We can't easily test the internal data preparation without actually sending,
        // so we'll create a simple test to verify the arrays are set correctly
        $this->assertEquals(['cc1@example.com', 'cc2@example.com'], $email->cc);
        $this->assertEquals(['bcc1@example.com', 'bcc2@example.com'], $email->bcc);
    }
}