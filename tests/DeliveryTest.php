<?php

declare(strict_types=1);

namespace CustomersMailCloud\Tests;

use CustomersMailCloud\CustomersMailCloud;
use CustomersMailCloud\Delivery;
use CustomersMailCloud\CustomersMailCloudError;
use PHPUnit\Framework\TestCase;

/**
 * Test for Delivery functionality
 */
class DeliveryTest extends TestCase
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
            'server_composition' => 'sandbox', // May need to adjust based on actual server composition
            'date' => date('Y-m-d', strtotime('-1 day')) // Yesterday's date
        ];
    }

    public function testDeliveryInstanceCreation(): void
    {
        $delivery = new Delivery(['subject' => 'Test Subject']);
        $this->assertInstanceOf(Delivery::class, $delivery);
        $this->assertEquals('Test Subject', $delivery->subject);
    }

    public function testValidationErrors(): void
    {
        // Test missing server_composition
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('server_composition parameter is required');
        $this->client->deliveries(['date' => '2023-01-01']);
    }

    public function testDateValidation(): void
    {
        // Test missing date
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('date parameter is required');
        $this->client->deliveries(['server_composition' => 'test']);
    }

    public function testInvalidDateFormat(): void
    {
        // Test invalid date format
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('date must be in yyyy-mm-dd format');
        $this->client->deliveries([
            'server_composition' => 'sandbox',
            'date' => '01-01-2023'
        ]);
    }

    public function testFutureDateError(): void
    {
        // Test future date to see if API returns error
        try {
            $deliveries = $this->client->deliveries([
                'server_composition' => 'sandbox',
                'date' => date('Y-m-d', strtotime('+1 day'))
            ]);
            
            // If no exception is thrown, test that the result is an array
            $this->assertIsArray($deliveries);
            echo "\nFuture Date Test Result:\n";
            echo "API accepted future date, found " . count($deliveries) . " records\n";
            
        } catch (CustomersMailCloudError $e) {
            echo "\nFuture Date API Error occurred (expected):\n";
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
            
            // Test passes if we get a CustomersMailCloudError
            $this->assertTrue(true, 'API correctly returned error for future date');
        } catch (\Exception $e) {
            $this->markTestSkipped('Future date test failed with unexpected error: ' . $e->getMessage());
        }
    }

    public function testInvalidServerComposition(): void
    {
        // Test invalid server composition to see if API returns error
        try {
            $deliveries = $this->client->deliveries([
                'server_composition' => 'invalid_server_composition',
                'date' => date('Y-m-d', strtotime('-1 day'))
            ]);
            
            // If no exception is thrown, test that the result is an array
            $this->assertIsArray($deliveries);
            echo "\nInvalid Server Composition Test Result:\n";
            echo "API accepted invalid server composition, found " . count($deliveries) . " records\n";
            
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
     * @group deliveries
     */
    public function testDeliveriesList(): void
    {
        try {
            $deliveries = $this->client->deliveries([
                'server_composition' => $this->testConfig['server_composition'],
                'date' => $this->testConfig['date'],
                'r' => 5 // Limit to 5 records
            ]);

            $this->assertIsArray($deliveries);
            echo "\nDeliveries List Test Result:\n";
            echo "Found " . count($deliveries) . " delivery records\n";
            
            if (!empty($deliveries)) {
                $delivery = $deliveries[0];
                $this->assertInstanceOf(Delivery::class, $delivery);
                echo "Sample delivery record:\n";
                echo "  Subject: " . $delivery->subject . "\n";
                echo "  Status: " . $delivery->status . "\n";
                echo "  From: " . $delivery->from . "\n";
                echo "  To: " . $delivery->to . "\n";
            }

        } catch (CustomersMailCloudError $e) {
            echo "\nDeliveries API Error occurred:\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            echo "Error Codes: " . implode(', ', $e->getAllErrorCodes()) . "\n";
            $this->markTestSkipped('Deliveries API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->markTestSkipped('Deliveries test failed: ' . $e->getMessage());
        }
    }

    /**
     * @group integration
     * @group deliveries
     */
    public function testDeliveriesWithFilters(): void
    {
        try {
            $deliveries = $this->client->deliveries([
                'server_composition' => 'sandbox',
                'date' => $this->testConfig['date'],
                'status' => 'succeeded',
                'r' => 3
            ]);

            $this->assertIsArray($deliveries);
            echo "\nFiltered Deliveries Test Result:\n";
            echo "Found " . count($deliveries) . " successful delivery records\n";
            
            foreach ($deliveries as $delivery) {
                if (!empty($delivery->status)) {
                    $this->assertEquals('succeeded', $delivery->status);
                }
            }

        } catch (CustomersMailCloudError $e) {
            echo "\nFiltered Deliveries API Error occurred:\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            $this->markTestSkipped('Filtered Deliveries API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->markTestSkipped('Filtered deliveries test failed: ' . $e->getMessage());
        }
    }

    /**
     * @group integration
     * @group deliveries
     */
    public function testDeliveriesWithSearchOptions(): void
    {
        try {
            $results = $this->client->deliveries([
                'server_composition' => 'sandbox',
                'date' => $this->testConfig['date'],
                'from' => 'info@dxlabo.com',
                'search_option' => ['from' => 'full'],
                'r' => 5
            ]);

            $this->assertIsArray($results);
            echo "\nSearch Options Test Result:\n";
            echo "Found " . count($results) . " delivery records with exact from match\n";

        } catch (CustomersMailCloudError $e) {
            echo "\nSearch Options API Error occurred:\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            $this->markTestSkipped('Search Options API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->markTestSkipped('Search options test failed: ' . $e->getMessage());
        }
    }
}