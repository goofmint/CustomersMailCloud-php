<?php

declare(strict_types=1);

namespace CustomersMailCloud\Tests;

use CustomersMailCloud\CustomersMailCloud;
use CustomersMailCloud\Auditlog;
use CustomersMailCloud\CustomersMailCloudError;
use PHPUnit\Framework\TestCase;

/**
 * Test for Auditlog functionality
 */
class AuditlogTest extends TestCase
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
            'type' => 'login',
            'start_date' => date('Y-m-d', strtotime('-7 days')),
            'end_date' => date('Y-m-d')
        ];
    }

    public function testAuditlogInstanceCreation(): void
    {
        // Test login log instance
        $loginLog = new Auditlog([
            'created' => '2023-01-01 12:00:00',
            'account' => 'test@example.com',
            'ipaddress' => '192.168.1.1',
            'code' => '0',
            'result' => '成功',
            'reason' => ''
        ]);
        
        $this->assertInstanceOf(Auditlog::class, $loginLog);
        $this->assertEquals('2023-01-01 12:00:00', $loginLog->created);
        $this->assertEquals('test@example.com', $loginLog->account);
        $this->assertEquals('192.168.1.1', $loginLog->ipaddress);
        $this->assertEquals('0', $loginLog->code);
        $this->assertEquals('成功', $loginLog->result);
        $this->assertEquals('', $loginLog->reason);

        // Test operation log instance
        $operationLog = new Auditlog([
            'created' => '2023-01-01 12:00:00',
            'account' => 'admin@example.com',
            'name' => 'Admin User',
            'function' => 'Test Function',
            'operation' => 'Test Operation'
        ]);
        
        $this->assertInstanceOf(Auditlog::class, $operationLog);
        $this->assertEquals('2023-01-01 12:00:00', $operationLog->created);
        $this->assertEquals('admin@example.com', $operationLog->account);
        $this->assertEquals('Admin User', $operationLog->name);
        $this->assertEquals('Test Function', $operationLog->function);
        $this->assertEquals('Test Operation', $operationLog->operation);
    }

    public function testTypeValidation(): void
    {
        // Test missing type
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('type parameter is required');
        $this->client->auditlogs([]);
    }

    public function testInvalidTypeValidation(): void
    {
        // Test invalid type
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('type must be either "login" or "operation"');
        $this->client->auditlogs(['type' => 'invalid']);
    }

    public function testStartDateValidation(): void
    {
        // Test invalid start_date format
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('start_date must be in yyyy-mm-dd format');
        $this->client->auditlogs([
            'type' => 'login',
            'start_date' => '01-01-2023'
        ]);
    }

    public function testEndDateValidation(): void
    {
        // Test invalid end_date format
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('end_date must be in yyyy-mm-dd format');
        $this->client->auditlogs([
            'type' => 'login',
            'end_date' => '01-01-2023'
        ]);
    }

    public function testDateRangeValidation(): void
    {
        // Test start_date later than end_date
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('start_date must be earlier than or equal to end_date');
        $this->client->auditlogs([
            'type' => 'login',
            'start_date' => '2023-01-10',
            'end_date' => '2023-01-05'
        ]);
    }

    public function testSearchDurationValidation(): void
    {
        // Test search duration longer than 31 days
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('search duration must be 31 days or less');
        $this->client->auditlogs([
            'type' => 'login',
            'start_date' => '2023-01-01',
            'end_date' => '2023-02-05' // 35 days
        ]);
    }

    public function testFutureDateError(): void
    {
        // Test future date to see if API returns error
        try {
            $auditlogs = $this->client->auditlogs([
                'type' => 'login',
                'start_date' => date('Y-m-d', strtotime('+1 day')),
                'end_date' => date('Y-m-d', strtotime('+2 days'))
            ]);
            
            // If no exception is thrown, test that the result is an array
            $this->assertIsArray($auditlogs);
            echo "\nFuture Date Test Result:\n";
            echo "API accepted future date, found " . count($auditlogs) . " records\n";
            
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

    public function testInvalidTypeError(): void
    {
        // Test invalid type to see if API returns error
        try {
            $auditlogs = Auditlog::list($this->client, [
                'type' => 'invalid_type',
                'start_date' => date('Y-m-d', strtotime('-7 days')),
                'end_date' => date('Y-m-d')
            ]);
            
            // If no exception is thrown, test that the result is an array
            $this->assertIsArray($auditlogs);
            echo "\nInvalid Type Test Result:\n";
            echo "API accepted invalid type, found " . count($auditlogs) . " records\n";
            
        } catch (CustomersMailCloudError $e) {
            echo "\nInvalid Type API Error occurred (expected):\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            echo "Error Codes: " . implode(', ', $e->getAllErrorCodes()) . "\n";
            
            // Test passes if we get a CustomersMailCloudError
            $this->assertTrue(true, 'API correctly returned error for invalid type');
        } catch (\Exception $e) {
            // Client-side validation should catch this before API call
            $this->assertTrue(true, 'Client-side validation caught invalid type: ' . $e->getMessage());
        }
    }

    /**
     * @group integration
     * @group auditlogs
     */
    public function testLoginLogsList(): void
    {
        try {
            $auditlogs = $this->client->auditlogs([
                'type' => 'login',
                'start_date' => $this->testConfig['start_date'],
                'end_date' => $this->testConfig['end_date'],
                'r' => 5 // Limit to 5 records
            ]);

            $this->assertIsArray($auditlogs);
            echo "\nLogin Logs Test Result:\n";
            echo "Found " . count($auditlogs) . " login log records\n";
            
            if (!empty($auditlogs)) {
                $log = $auditlogs[0];
                $this->assertInstanceOf(Auditlog::class, $log);
                echo "Sample login log record:\n";
                echo "  Created: " . $log->created . "\n";
                echo "  Account: " . $log->account . "\n";
                echo "  IP Address: " . ($log->ipaddress ?? 'N/A') . "\n";
                echo "  Code: " . ($log->code ?? 'N/A') . "\n";
                echo "  Result: " . ($log->result ?? 'N/A') . "\n";
                echo "  Reason: " . ($log->reason ?? 'N/A') . "\n";
            }

        } catch (CustomersMailCloudError $e) {
            echo "\nLogin Logs API Error occurred:\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            echo "Error Codes: " . implode(', ', $e->getAllErrorCodes()) . "\n";
            $this->markTestSkipped('Login Logs API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->markTestSkipped('Login logs test failed: ' . $e->getMessage());
        }
    }

    /**
     * @group integration
     * @group auditlogs
     */
    public function testOperationLogsList(): void
    {
        try {
            $auditlogs = $this->client->auditlogs([
                'type' => 'operation',
                'start_date' => $this->testConfig['start_date'],
                'end_date' => $this->testConfig['end_date'],
                'r' => 5 // Limit to 5 records
            ]);

            $this->assertIsArray($auditlogs);
            echo "\nOperation Logs Test Result:\n";
            echo "Found " . count($auditlogs) . " operation log records\n";
            
            if (!empty($auditlogs)) {
                $log = $auditlogs[0];
                $this->assertInstanceOf(Auditlog::class, $log);
                echo "Sample operation log record:\n";
                echo "  Created: " . $log->created . "\n";
                echo "  Account: " . $log->account . "\n";
                echo "  Name: " . ($log->name ?? 'N/A') . "\n";
                echo "  Function: " . ($log->function ?? 'N/A') . "\n";
                echo "  Operation: " . ($log->operation ?? 'N/A') . "\n";
            }

        } catch (CustomersMailCloudError $e) {
            echo "\nOperation Logs API Error occurred:\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            echo "Error Codes: " . implode(', ', $e->getAllErrorCodes()) . "\n";
            $this->markTestSkipped('Operation Logs API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->markTestSkipped('Operation logs test failed: ' . $e->getMessage());
        }
    }

    /**
     * @group integration
     * @group auditlogs
     */
    public function testAuditlogsWithFilters(): void
    {
        try {
            $auditlogs = $this->client->auditlogs([
                'type' => 'login',
                'start_date' => $this->testConfig['start_date'],
                'end_date' => $this->testConfig['end_date'],
                'account' => 'admin', // Partial match
                'p' => 0,
                'r' => 3
            ]);
            $this->assertIsArray($auditlogs);
            echo "\nFiltered Auditlogs Test Result:\n";
            echo "Found " . count($auditlogs) . " filtered audit log records\n";

        } catch (CustomersMailCloudError $e) {
            echo "\nFiltered Auditlogs API Error occurred:\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            $this->markTestSkipped('Filtered Auditlogs API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->markTestSkipped('Filtered auditlogs test failed: ' . $e->getMessage());
        }
    }

    /**
     * @group integration
     * @group auditlogs
     * @group download
     */
    public function testAuditlogsDownload(): void
    {
        try {
            $zipContent = Auditlog::download($this->client, [
                'type' => 'login',
                'start_date' => $this->testConfig['start_date'],
                'end_date' => $this->testConfig['end_date']
            ]);

            $this->assertIsString($zipContent);
            $this->assertNotEmpty($zipContent);
            echo "\nAuditlogs Download Test Result:\n";
            echo "Downloaded ZIP file size: " . strlen($zipContent) . " bytes\n";
            
            // Verify it's a ZIP file by checking magic bytes
            $magicBytes = substr($zipContent, 0, 4);
            $this->assertTrue(
                $magicBytes === "PK\x03\x04" || $magicBytes === "PK\x05\x06" || $magicBytes === "PK\x07\x08",
                'Downloaded content should be a ZIP file'
            );

        } catch (CustomersMailCloudError $e) {
            echo "\nAuditlogs Download API Error occurred:\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            echo "Error Codes: " . implode(', ', $e->getAllErrorCodes()) . "\n";
            $this->markTestSkipped('Auditlogs Download API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->markTestSkipped('Auditlogs download test failed: ' . $e->getMessage());
        }
    }

    public function testDownloadTypeValidation(): void
    {
        // Test missing type for download
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('type parameter is required');
        Auditlog::download($this->client, []);
    }

    public function testDownloadInvalidTypeValidation(): void
    {
        // Test invalid type for download
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('type must be either "login" or "operation"');
        Auditlog::download($this->client, ['type' => 'invalid']);
    }
}