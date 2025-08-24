<?php

declare(strict_types=1);

namespace CustomersMailCloud\Tests;

use CustomersMailCloud\CustomersMailCloud;
use CustomersMailCloud\CustomersMailCloudError;
use PHPUnit\Framework\TestCase;

/**
 * Test for error handling functionality
 * This test verifies that API errors are properly caught and handled
 */
class ErrorHandlingTest extends TestCase
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
            'from' => $env['TEST_FROM'] ?? '',
            'from_name' => $env['TEST_FROM_NAME'] ?? '',
        ];
    }

    /**
     * @group error-handling
     */
    public function testDKIMErrorHandling(): void
    {
        if (empty($this->testConfig['from'])) {
            $this->markTestSkipped('TEST_FROM is required for error handling test');
        }

        $email = $this->client->transaction_email();
        
        // Use an invalid from address that should trigger DKIM error
        $email->add_to('test@example.com', 'Test User')
              ->set_from('invalid@invalid-domain.com', 'Invalid Sender');
              
        $email->subject = 'DKIM Error Test';
        $email->text = 'This should trigger a DKIM error.';

        $this->expectException(CustomersMailCloudError::class);
        
        try {
            $email->send();
        } catch (CustomersMailCloudError $e) {
            // Verify error details
            $this->assertTrue($e->hasErrorCode('13-006'));
            $this->assertStringContainsString('DKIM', $e->getMessage());
            
            $errorInfo = $e->getErrorInfo();
            $this->assertArrayHasKey('errors', $errorInfo);
            $this->assertArrayHasKey('error_codes', $errorInfo);
            
            // Re-throw to satisfy expectException
            throw $e;
        }
    }

    /**
     * @group error-handling  
     */
    public function testActualDKIMError(): void
    {
        if (empty($this->testConfig['from'])) {
            $this->markTestSkipped('TEST_FROM is required for error handling test');
        }

        $email = $this->client->transaction_email();
        
        // Use the configured from address which might have DKIM issues
        $email->add_to('test@example.com', 'Test User')
              ->set_from($this->testConfig['from'], $this->testConfig['from_name']);
              
        $email->subject = 'Real DKIM Error Test';
        $email->text = 'This tests real DKIM configuration.';

        try {
            $result = $email->send();
            
            // If it succeeds, that's fine too
            $this->assertTrue($result);
            $this->assertNotNull($email->id);
            echo "\nEmail sent successfully (DKIM configured properly): " . $email->id . "\n";
            
        } catch (CustomersMailCloudError $e) {
            // This is expected if DKIM is not set up
            echo "\nExpected DKIM Error: " . $e->getMessage() . "\n";
            echo "Error Codes: " . implode(', ', $e->getAllErrorCodes()) . "\n";
            
            $this->assertTrue($e->hasErrorCode('13-006'));
            
        } catch (\Exception $e) {
            $this->fail('Unexpected error type: ' . $e->getMessage());
        }
    }

    /**
     * @group error-handling
     */
    public function testMissingRequiredFieldsError(): void
    {
        $email = $this->client->transaction_email();
        
        // Don't set required fields to trigger validation errors
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('At least one recipient is required');
        
        $email->send();
    }

    /**
     * @group error-handling
     */
    public function testErrorInfoStructure(): void
    {
        $errors = [
            [
                'code' => '13-006',
                'field' => '',
                'message' => 'Can not send email with specified from address. Please set up DKIM.'
            ]
        ];
        $rawResponse = ['errors' => $errors];

        $error = new CustomersMailCloudError($errors, $rawResponse);
        $errorInfo = $error->getErrorInfo();

        // Verify structure
        $this->assertIsArray($errorInfo);
        $this->assertArrayHasKey('message', $errorInfo);
        $this->assertArrayHasKey('errors', $errorInfo);
        $this->assertArrayHasKey('error_codes', $errorInfo);
        $this->assertArrayHasKey('raw_response', $errorInfo);

        // Verify content
        $this->assertEquals($error->getMessage(), $errorInfo['message']);
        $this->assertEquals($errors, $errorInfo['errors']);
        $this->assertEquals(['13-006'], $errorInfo['error_codes']);
        $this->assertEquals($rawResponse, $errorInfo['raw_response']);
    }
}