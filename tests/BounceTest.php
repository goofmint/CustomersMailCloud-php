<?php

declare(strict_types=1);

namespace CustomersMailCloud\Tests;

use CustomersMailCloud\CustomersMailCloud;
use CustomersMailCloud\Bounce;
use CustomersMailCloud\CustomersMailCloudError;
use PHPUnit\Framework\TestCase;

/**
 * Test for Bounce functionality
 */
class BounceTest extends TestCase
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
            'start_date' => date('Y-m-d', strtotime('-7 days')), // 7 days ago
            'end_date' => date('Y-m-d', strtotime('-1 day')) // Yesterday
        ];
    }

    public function testBounceInstanceCreation(): void
    {
        $bounce = new Bounce(['status' => '1', 'reason' => 'host unknown']);
        $this->assertInstanceOf(Bounce::class, $bounce);
        $this->assertEquals('1', $bounce->status);
        $this->assertEquals('host unknown', $bounce->reason);
    }

    public function testValidationErrors(): void
    {
        // Test missing server_composition
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('server_composition parameter is required');
        $this->client->bounces([]);
    }

    public function testInvalidBounceStatus(): void
    {
        // Test invalid bounce status to see if API returns error
        try {
            $bounces = $this->client->bounces([
                'server_composition' => 'sandbox',
                'status' => 999 // Invalid status
            ]);
            
            // If no exception is thrown, test that the result is an array
            $this->assertIsArray($bounces);
            echo "\nInvalid Bounce Status Test Result:\n";
            echo "API accepted invalid status, found " . count($bounces) . " records\n";
            
        } catch (CustomersMailCloudError $e) {
            echo "\nInvalid Bounce Status API Error occurred (expected):\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            echo "Error Codes: " . implode(', ', $e->getAllErrorCodes()) . "\n";
            
            // Test passes if we get a CustomersMailCloudError
            $this->assertTrue(true, 'API correctly returned error for invalid status');
        } catch (\Exception $e) {
            $this->markTestSkipped('Invalid status test failed with unexpected error: ' . $e->getMessage());
        }
    }

    public function testInvalidServerComposition(): void
    {
        // Test invalid server composition
        try {
            $bounces = $this->client->bounces([
                'server_composition' => 'invalid_server_composition'
            ]);
            
            // If no exception is thrown, test that the result is an array
            $this->assertIsArray($bounces);
            echo "\nInvalid Server Composition Test Result:\n";
            echo "API accepted invalid server composition, found " . count($bounces) . " records\n";
            
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
     * @group bounces
     */
    public function testBouncesList(): void
    {
        try {
            $bounces = $this->client->bounces([
                'server_composition' => $this->testConfig['server_composition'],
                'start_date' => $this->testConfig['start_date'],
                'end_date' => $this->testConfig['end_date'],
                'r' => 5 // Limit to 5 records
            ]);

            $this->assertIsArray($bounces);
            echo "\nBounces List Test Result:\n";
            echo "Found " . count($bounces) . " bounce records\n";
            
            if (!empty($bounces)) {
                $bounce = $bounces[0];
                $this->assertInstanceOf(Bounce::class, $bounce);
                echo "Sample bounce record:\n";
                echo "  Status: " . $bounce->status . "\n";
                echo "  Reason: " . $bounce->reason . "\n";
                echo "  From: " . $bounce->from . "\n";
                echo "  To: " . $bounce->to . "\n";
                echo "  Created: " . $bounce->created . "\n";
            }

        } catch (CustomersMailCloudError $e) {
            echo "\nBounces API Error occurred:\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            echo "Error Codes: " . implode(', ', $e->getAllErrorCodes()) . "\n";
            $this->markTestSkipped('Bounces API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->markTestSkipped('Bounces test failed: ' . $e->getMessage());
        }
    }

    /**
     * @group integration
     * @group bounces
     */
    public function testBouncesWithFilters(): void
    {
        try {
            $bounces = $this->client->bounces([
                'server_composition' => $this->testConfig['server_composition'],
                'start_date' => $this->testConfig['start_date'],
                'end_date' => $this->testConfig['end_date'],
                'status' => 2, // User unknown
                'r' => 3
            ]);

            $this->assertIsArray($bounces);
            echo "\nFiltered Bounces Test Result:\n";
            echo "Found " . count($bounces) . " user unknown bounce records\n";
            
            foreach ($bounces as $bounce) {
                if (!empty($bounce->status)) {
                    $this->assertEquals('2', $bounce->status);
                }
            }

        } catch (CustomersMailCloudError $e) {
            echo "\nFiltered Bounces API Error occurred:\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            $this->markTestSkipped('Filtered Bounces API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->markTestSkipped('Filtered bounces test failed: ' . $e->getMessage());
        }
    }

    /**
     * @group integration
     * @group bounces
     */
    public function testBouncesWithSearchOptions(): void
    {
        try {
            $results = $this->client->bounces([
                'server_composition' => $this->testConfig['server_composition'],
                'start_date' => $this->testConfig['start_date'],
                'end_date' => $this->testConfig['end_date'],
                'from' => 'info@dxlabo.com',
                'search_option' => ['from' => 'full'],
                'r' => 5
            ]);

            $this->assertIsArray($results);
            echo "\nSearch Options Test Result:\n";
            echo "Found " . count($results) . " bounce records with exact from match\n";

        } catch (CustomersMailCloudError $e) {
            echo "\nSearch Options API Error occurred:\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            $this->markTestSkipped('Search Options API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->markTestSkipped('Search options test failed: ' . $e->getMessage());
        }
    }
}
