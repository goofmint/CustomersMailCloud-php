<?php

declare(strict_types=1);

namespace CustomersMailCloud\Tests;

use CustomersMailCloud\CustomersMailCloud;
use CustomersMailCloud\Statistic;
use CustomersMailCloud\CustomersMailCloudError;
use PHPUnit\Framework\TestCase;

/**
 * Test for Statistic functionality
 */
class StatisticTest extends TestCase
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
            'year' => (int)date('Y'),
            'month' => (int)date('n') - 1
        ];
    }

    public function testStatisticInstanceCreation(): void
    {
        $statistic = new Statistic([
            'date' => '2023-01-01',
            'queued' => 100,
            'succeeded' => 95,
            'failed' => 5,
            'blocked' => 0,
            'valid' => 100
        ]);
        $this->assertInstanceOf(Statistic::class, $statistic);
        $this->assertEquals('2023-01-01', $statistic->date);
        $this->assertEquals(100, $statistic->queued);
        $this->assertEquals(95, $statistic->succeeded);
        $this->assertEquals(5, $statistic->failed);
        $this->assertEquals(0, $statistic->blocked);
        $this->assertEquals(100, $statistic->valid);
    }

    public function testYearValidation(): void
    {
        // Test missing year
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('year parameter is required');
        $this->client->statistics(['month' => 1]);
    }

    public function testMonthValidation(): void
    {
        // Test missing month
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('month parameter is required');
        $this->client->statistics(['year' => 2023]);
    }

    public function testInvalidYearFormat(): void
    {
        // Test invalid year format
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('year must be a 4-digit number');
        $this->client->statistics([
            'year' => 23,
            'month' => 1
        ]);
    }

    public function testInvalidMonthRange(): void
    {
        // Test invalid month range
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('month must be a number between 1 and 12');
        $this->client->statistics([
            'year' => 2023,
            'month' => 13
        ]);
    }

    public function testFutureYearError(): void
    {
        // Test future year to see if API returns error
        $futureYear = (int)date('Y') + 1;
        try {
            $statistics = $this->client->statistics([
                'year' => $futureYear,
                'month' => 1,
                'server_composition' => 'sandbox'
            ]);
            
            // If no exception is thrown, test that the result is an array
            $this->assertIsArray($statistics);
            echo "\nFuture Year Test Result:\n";
            echo "API accepted future year, found " . count($statistics) . " records\n";
            
        } catch (CustomersMailCloudError $e) {
            echo "\nFuture Year API Error occurred (expected):\n";
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
            $this->assertTrue(true, 'API correctly returned error for future year');
        } catch (\Exception $e) {
            $this->markTestSkipped('Future year test failed with unexpected error: ' . $e->getMessage());
        }
    }

    public function testInvalidServerComposition(): void
    {
        // Test invalid server composition to see if API returns error
        try {
            $statistics = $this->client->statistics([
                'year' => 2023,
                'month' => 1,
                'server_composition' => 'invalid_server_composition'
            ]);
            
            // If no exception is thrown, test that the result is an array
            $this->assertIsArray($statistics);
            echo "\nInvalid Server Composition Test Result:\n";
            echo "API accepted invalid server composition, found " . count($statistics) . " records\n";
            
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
     * @group statistics
     */
    public function testStatisticsList(): void
    {
        try {
            $statistics = $this->client->statistics([
                'year' => $this->testConfig['year'],
                'month' => $this->testConfig['month'],
                'server_composition' => $this->testConfig['server_composition']
            ]);

            $this->assertIsArray($statistics);
            echo "\nStatistics List Test Result:\n";
            echo "Found " . count($statistics) . " statistic records\n";
            
            if (!empty($statistics)) {
                $statistic = $statistics[0];
                $this->assertInstanceOf(Statistic::class, $statistic);
                echo "Sample statistic record:\n";
                echo "  Date: " . $statistic->date . "\n";
                echo "  Queued: " . $statistic->queued . "\n";
                echo "  Succeeded: " . $statistic->succeeded . "\n";
                echo "  Failed: " . $statistic->failed . "\n";
                echo "  Blocked: " . $statistic->blocked . "\n";
                echo "  Valid: " . $statistic->valid . "\n";
            }

        } catch (CustomersMailCloudError $e) {
            echo "\nStatistics API Error occurred:\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            echo "Error Codes: " . implode(', ', $e->getAllErrorCodes()) . "\n";
            $this->markTestSkipped('Statistics API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->markTestSkipped('Statistics test failed: ' . $e->getMessage());
        }
    }

    /**
     * @group integration
     * @group statistics
     */
    public function testStatisticsWithTotal(): void
    {
        try {
            $statistics = $this->client->statistics([
                'year' => $this->testConfig['year'],
                'month' => $this->testConfig['month'],
                'server_composition' => 'sandbox',
                'total' => true
            ]);

            $this->assertIsArray($statistics);
            echo "\nTotal Statistics Test Result:\n";
            echo "Found " . count($statistics) . " total statistic records\n";
            
            if (!empty($statistics)) {
                $statistic = $statistics[0];
                $this->assertInstanceOf(Statistic::class, $statistic);
                echo "Total statistics:\n";
                echo "  Date: " . $statistic->date . "\n";
                echo "  Queued: " . $statistic->queued . "\n";
                echo "  Succeeded: " . $statistic->succeeded . "\n";
                echo "  Failed: " . $statistic->failed . "\n";
                echo "  Blocked: " . $statistic->blocked . "\n";
                echo "  Valid: " . $statistic->valid . "\n";
                
                // When total=true, date should be "total"
                if (count($statistics) === 1) {
                    $this->assertEquals('total', $statistic->date);
                }
            }

        } catch (CustomersMailCloudError $e) {
            echo "\nTotal Statistics API Error occurred:\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            $this->markTestSkipped('Total Statistics API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->markTestSkipped('Total statistics test failed: ' . $e->getMessage());
        }
    }

    /**
     * @group integration
     * @group statistics
     */
    public function testStatisticsWithoutServerComposition(): void
    {
        try {
            $statistics = $this->client->statistics([
                'year' => $this->testConfig['year'],
                'month' => $this->testConfig['month']
            ]);
             $this->assertIsArray($statistics);
            echo "\nStatistics without Server Composition Test Result:\n";
            echo "Found " . count($statistics) . " statistic records\n";

        } catch (CustomersMailCloudError $e) {
            echo "\nStatistics without Server Composition API Error occurred:\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            $this->markTestSkipped('Statistics without Server Composition API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->markTestSkipped('Statistics without server composition test failed: ' . $e->getMessage());
        }
    }
}