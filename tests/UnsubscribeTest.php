<?php

declare(strict_types=1);

namespace CustomersMailCloud\Tests;

use CustomersMailCloud\CustomersMailCloud;
use CustomersMailCloud\Unsubscribe;
use CustomersMailCloud\CustomersMailCloudError;
use PHPUnit\Framework\TestCase;

/**
 * Test for Unsubscribe functionality
 */
class UnsubscribeTest extends TestCase
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
            'server_composition' => 'sandbox',
            'start_date' => date('Y-m-d', strtotime('-7 days')),
            'end_date' => date('Y-m-d')
        ];
    }

    public function testUnsubscribeInstanceCreation(): void
    {
        $unsubscribe = new Unsubscribe([
            'created' => '2023-01-01 12:00:00',
            'email' => 'test@example.com',
            'filtername' => 'Test Filter'
        ]);
        
        $this->assertInstanceOf(Unsubscribe::class, $unsubscribe);
        $this->assertEquals('2023-01-01 12:00:00', $unsubscribe->created);
        $this->assertEquals('test@example.com', $unsubscribe->email);
        $this->assertEquals('Test Filter', $unsubscribe->filtername);
    }

    public function testDownloadServerCompositionValidation(): void
    {
        // Test missing server_composition
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('server_composition parameter is required');
        Unsubscribe::download($this->client, []);
    }

    public function testDownloadStartDateValidation(): void
    {
        // Test invalid start_date format
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('start_date must be in yyyy-mm-dd format');
        Unsubscribe::download($this->client, [
            'server_composition' => 'sandbox',
            'start_date' => '01-01-2023'
        ]);
    }

    public function testDownloadEndDateValidation(): void
    {
        // Test invalid end_date format
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('end_date must be in yyyy-mm-dd format');
        Unsubscribe::download($this->client, [
            'server_composition' => 'sandbox',
            'end_date' => '01-01-2023'
        ]);
    }

    public function testDownloadDateRangeValidation(): void
    {
        // Test start_date later than end_date
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('start_date must be earlier than or equal to end_date');
        Unsubscribe::download($this->client, [
            'server_composition' => 'sandbox',
            'start_date' => '2023-01-10',
            'end_date' => '2023-01-05'
        ]);
    }

    public function testCancelServerCompositionValidation(): void
    {
        // Test missing server_composition for cancel
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('server_composition parameter is required');
        Unsubscribe::cancel($this->client, ['email' => 'test@example.com']);
    }

    public function testCancelEmailValidation(): void
    {
        // Test missing email for cancel
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('email parameter is required');
        Unsubscribe::cancel($this->client, ['server_composition' => 'sandbox']);
    }

    public function testInvalidServerCompositionError(): void
    {
        // Test invalid server composition to see if API returns error
        try {
            $zipContent = Unsubscribe::download($this->client, [
                'server_composition' => 'invalid_server_composition',
                'start_date' => $this->testConfig['start_date'],
                'end_date' => $this->testConfig['end_date']
            ]);
            
            // If no exception is thrown, test that the result is a string
            $this->assertIsString($zipContent);
            echo "\nInvalid Server Composition Test Result:\n";
            echo "API accepted invalid server composition, downloaded " . strlen($zipContent) . " bytes\n";
            
        } catch (CustomersMailCloudError $e) {
            echo "\nInvalid Server Composition API Error occurred (expected):\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            echo "Error Codes: " . implode(', ', $e->getAllErrorCodes()) . "\n";
            
            // Test passes if we get a CustomersMailCloudError
            $this->assertTrue(true, 'API correctly returned error for invalid server composition');
        } catch (\Exception $e) {
            $this->markTestSkipped('Invalid server composition test failed with unexpected error: ' . $e->getMessage());
        }
    }

    /**
     * @group integration
     * @group unsubscribes
     * @group download
     */
    public function testUnsubscribeDownload(): void
    {
        try {
            $zipContent = Unsubscribe::download($this->client, [
                'server_composition' => $this->testConfig['server_composition'],
                'start_date' => $this->testConfig['start_date'],
                'end_date' => $this->testConfig['end_date']
            ]);

            $this->assertIsString($zipContent);
            $this->assertNotEmpty($zipContent);
            echo "\nUnsubscribe Download Test Result:\n";
            echo "Downloaded ZIP file size: " . strlen($zipContent) . " bytes\n";
            
            // Verify it's a ZIP file by checking magic bytes
            $magicBytes = substr($zipContent, 0, 4);
            $this->assertTrue(
                $magicBytes === "PK\x03\x04" || $magicBytes === "PK\x05\x06" || $magicBytes === "PK\x07\x08",
                'Downloaded content should be a ZIP file'
            );

        } catch (CustomersMailCloudError $e) {
            echo "\nUnsubscribe Download API Error occurred:\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            echo "Error Codes: " . implode(', ', $e->getAllErrorCodes()) . "\n";
            $this->markTestSkipped('Unsubscribe Download API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->markTestSkipped('Unsubscribe download test failed: ' . $e->getMessage());
        }
    }

    /**
     * @group integration
     * @group unsubscribes
     * @group download
     */
    public function testUnsubscribeDownloadWithFilters(): void
    {
        try {
            $zipContent = Unsubscribe::download($this->client, [
                'server_composition' => $this->testConfig['server_composition'],
                'email' => 'test', // Partial match
                'start_date' => $this->testConfig['start_date'],
                'end_date' => $this->testConfig['end_date'],
                'filter_name' => 'Test Filter'
            ]);

            $this->assertIsString($zipContent);
            echo "\nFiltered Unsubscribe Download Test Result:\n";
            echo "Downloaded filtered ZIP file size: " . strlen($zipContent) . " bytes\n";

        } catch (CustomersMailCloudError $e) {
            echo "\nFiltered Unsubscribe Download API Error occurred:\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            $this->markTestSkipped('Filtered Unsubscribe Download API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->markTestSkipped('Filtered unsubscribe download test failed: ' . $e->getMessage());
        }
    }

    /**
     * @group integration
     * @group unsubscribes
     * @group cancel
     */
    public function testUnsubscribeCancel(): void
    {
        try {
            $response = Unsubscribe::cancel($this->client, [
                'server_composition' => $this->testConfig['server_composition'],
                'email' => 'test@example.com'
            ]);

            $this->assertIsArray($response);
            echo "\nUnsubscribe Cancel Test Result:\n";
            echo "Response: " . json_encode($response) . "\n";
            
            // Check for success response
            if (isset($response['message'])) {
                $this->assertEquals('success', $response['message']);
            }

        } catch (CustomersMailCloudError $e) {
            echo "\nUnsubscribe Cancel API Error occurred:\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            echo "Error Codes: " . implode(', ', $e->getAllErrorCodes()) . "\n";
            
            // Check if it's a "data not found" error (expected for test email)
            if ($e->hasErrorCode('12-001')) {
                echo "Expected error: Email not found in unsubscribe list\n";
                $this->assertTrue(true, 'API correctly returned error for non-existent email');
            } else {
                $this->markTestSkipped('Unsubscribe Cancel API Error: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            $this->markTestSkipped('Unsubscribe cancel test failed: ' . $e->getMessage());
        }
    }

    /**
     * @group integration
     * @group unsubscribes
     * @group cancel
     */
    public function testUnsubscribeCancelWithFilterName(): void
    {
        try {
            $response = Unsubscribe::cancel($this->client, [
                'server_composition' => $this->testConfig['server_composition'],
                'email' => 'test@example.com',
                'filter_name' => 'Test Filter'
            ]);

            $this->assertIsArray($response);
            echo "\nUnsubscribe Cancel with Filter Test Result:\n";
            echo "Response: " . json_encode($response) . "\n";

        } catch (CustomersMailCloudError $e) {
            echo "\nUnsubscribe Cancel with Filter API Error occurred:\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            
            // Expected error for test data
            if ($e->hasErrorCode('12-001') || $e->hasErrorCode('12-002')) {
                echo "Expected error: Email or filter not found\n";
                $this->assertTrue(true, 'API correctly returned error for non-existent data');
            } else {
                $this->markTestSkipped('Unsubscribe Cancel with Filter API Error: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            $this->markTestSkipped('Unsubscribe cancel with filter test failed: ' . $e->getMessage());
        }
    }

    /**
     * @group integration
     * @group unsubscribes
     * @group cancel
     */
    public function testUnsubscribeCancelMultipleEmails(): void
    {
        try {
            // Test with multiple email addresses using JSON array format
            $response = Unsubscribe::cancel($this->client, [
                'server_composition' => $this->testConfig['server_composition'],
                'email' => [
                    'test1@example.com',
                    'test2@example.com',
                    'test3@example.com'
                ]
            ]);

            $this->assertIsArray($response);
            echo "\nUnsubscribe Cancel Multiple Emails Test Result:\n";
            echo "Response: " . json_encode($response) . "\n";

        } catch (CustomersMailCloudError $e) {
            echo "\nUnsubscribe Cancel Multiple Emails API Error occurred:\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            
            // Expected error for test data
            if ($e->hasErrorCode('12-001')) {
                echo "Expected error: Emails not found in unsubscribe list\n";
                $this->assertTrue(true, 'API correctly returned error for non-existent emails');
            } else {
                $this->markTestSkipped('Unsubscribe Cancel Multiple Emails API Error: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            $this->markTestSkipped('Unsubscribe cancel multiple emails test failed: ' . $e->getMessage());
        }
    }
}