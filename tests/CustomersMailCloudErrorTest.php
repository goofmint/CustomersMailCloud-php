<?php

declare(strict_types=1);

namespace CustomersMailCloud\Tests;

use CustomersMailCloud\CustomersMailCloudError;
use PHPUnit\Framework\TestCase;

class CustomersMailCloudErrorTest extends TestCase
{
    public function testCreateErrorWithSingleError(): void
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

        $this->assertEquals('[13-006] Can not send email with specified from address. Please set up DKIM.', $error->getMessage());
        $this->assertEquals('13-006', $error->getErrorCode());
        $this->assertEquals('', $error->getErrorField());
        $this->assertEquals($errors, $error->errors);
        $this->assertEquals($rawResponse, $error->rawResponse);
    }

    public function testCreateErrorWithMultipleErrors(): void
    {
        $errors = [
            [
                'code' => '10-001',
                'field' => 'subject',
                'message' => 'Subject is required'
            ],
            [
                'code' => '10-002',
                'field' => 'text',
                'message' => 'Text content is required'
            ]
        ];
        $rawResponse = ['errors' => $errors];

        $error = new CustomersMailCloudError($errors, $rawResponse);

        $expectedMessage = '[10-001] Subject is required (field: subject); [10-002] Text content is required (field: text)';
        $this->assertEquals($expectedMessage, $error->getMessage());
        $this->assertEquals('10-001', $error->getErrorCode());
        $this->assertEquals('subject', $error->getErrorField());
    }

    public function testCreateErrorWithCustomMessage(): void
    {
        $errors = [
            [
                'code' => '13-006',
                'field' => '',
                'message' => 'Can not send email with specified from address. Please set up DKIM.'
            ]
        ];
        $rawResponse = ['errors' => $errors];
        $customMessage = 'Custom error message';

        $error = new CustomersMailCloudError($errors, $rawResponse, $customMessage);

        $this->assertEquals($customMessage, $error->getMessage());
    }

    public function testGetAllErrorCodes(): void
    {
        $errors = [
            ['code' => '10-001', 'field' => 'subject', 'message' => 'Subject error'],
            ['code' => '10-002', 'field' => 'text', 'message' => 'Text error'],
            ['code' => '', 'field' => 'other', 'message' => 'Other error'] // Empty code should be ignored
        ];
        $rawResponse = ['errors' => $errors];

        $error = new CustomersMailCloudError($errors, $rawResponse);

        $this->assertEquals(['10-001', '10-002'], $error->getAllErrorCodes());
    }

    public function testHasErrorCode(): void
    {
        $errors = [
            ['code' => '13-006', 'field' => '', 'message' => 'DKIM error'],
            ['code' => '10-001', 'field' => 'subject', 'message' => 'Subject error']
        ];
        $rawResponse = ['errors' => $errors];

        $error = new CustomersMailCloudError($errors, $rawResponse);

        $this->assertTrue($error->hasErrorCode('13-006'));
        $this->assertTrue($error->hasErrorCode('10-001'));
        $this->assertFalse($error->hasErrorCode('99-999'));
    }

    public function testGetErrorInfo(): void
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

        $this->assertArrayHasKey('message', $errorInfo);
        $this->assertArrayHasKey('errors', $errorInfo);
        $this->assertArrayHasKey('error_codes', $errorInfo);
        $this->assertArrayHasKey('raw_response', $errorInfo);

        $this->assertEquals($error->getMessage(), $errorInfo['message']);
        $this->assertEquals($errors, $errorInfo['errors']);
        $this->assertEquals(['13-006'], $errorInfo['error_codes']);
        $this->assertEquals($rawResponse, $errorInfo['raw_response']);
    }

    public function testCreateErrorWithEmptyErrors(): void
    {
        $errors = [];
        $rawResponse = ['errors' => $errors];

        $error = new CustomersMailCloudError($errors, $rawResponse);

        $this->assertEquals('Unknown API error occurred', $error->getMessage());
        $this->assertEquals('', $error->getErrorCode());
        $this->assertEquals('', $error->getErrorField());
        $this->assertEquals([], $error->getAllErrorCodes());
    }

    public function testCreateErrorWithIncompleteErrorData(): void
    {
        $errors = [
            [
                'message' => 'Error without code or field'
            ],
            [
                'code' => '10-001'
                // Missing message and field
            ]
        ];
        $rawResponse = ['errors' => $errors];

        $error = new CustomersMailCloudError($errors, $rawResponse);

        $this->assertStringContainsString('Error without code or field', $error->getMessage());
        $this->assertStringContainsString('Unknown error', $error->getMessage());
    }
}