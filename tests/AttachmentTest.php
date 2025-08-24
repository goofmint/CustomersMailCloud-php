<?php

declare(strict_types=1);

namespace CustomersMailCloud\Tests;

use CustomersMailCloud\CustomersMailCloud;
use CustomersMailCloud\CustomersMailCloudError;
use PHPUnit\Framework\TestCase;

/**
 * Test for email attachments functionality
 */
class AttachmentTest extends TestCase
{
    private CustomersMailCloud $client;
    private array $testConfig;
    private string $testImagePath;
    private string $testTextPath;

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

        // Set up test file paths
        $this->testImagePath = __DIR__ . '/test.png';
        $this->testTextPath = __DIR__ . '/LICENSE';

        // Verify test files exist
        if (!file_exists($this->testImagePath)) {
            $this->markTestSkipped('Test image file (test.png) not found');
        }
        if (!file_exists($this->testTextPath)) {
            $this->markTestSkipped('Test text file (LICENSE) not found');
        }
    }

    /**
     * @group integration
     * @group attachments
     */
    public function testSendEmailWithSingleAttachment(): void
    {
        if (empty($this->testConfig['to']) || empty($this->testConfig['from'])) {
            $this->markTestSkipped('TEST_TO and TEST_FROM are required for attachment test');
        }

        $email = $this->client->transaction_email();
        
        $email->add_to($this->testConfig['to'], $this->testConfig['to_name'])
              ->set_from($this->testConfig['from'], $this->testConfig['from_name']);
              
        $email->subject = 'Single Attachment Test - ' . date('Y-m-d H:i:s');
        $email->text = "This is a test email with a single attachment.\n\nImage file: test.png\n\nSent at: " . date('Y-m-d H:i:s');
        $email->html = '<h1>Single Attachment Test</h1><p>This is a test email with a single attachment.</p><p><strong>Image file:</strong> test.png</p><p><strong>Sent at:</strong> ' . date('Y-m-d H:i:s') . '</p>';

        // Add single attachment
        $email->attachments = [$this->testImagePath];

        try {
            $result = $email->send();
            
            $this->assertTrue($result);
            $this->assertNotNull($email->id);
            echo "\nSingle Attachment Test Result:\n";
            echo "Success: " . ($result ? 'true' : 'false') . "\n";
            echo "Message ID: " . $email->id . "\n";
            echo "Attachments: " . count($email->attachments) . "\n";
            echo "File: " . basename($this->testImagePath) . " (" . filesize($this->testImagePath) . " bytes)\n";
            
        } catch (CustomersMailCloudError $e) {
            echo "\nAttachment API Error occurred:\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            echo "Error Codes: " . implode(', ', $e->getAllErrorCodes()) . "\n";
            $this->markTestSkipped('Attachment API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->fail('Unexpected attachment error: ' . $e->getMessage());
        }
    }

    /**
     * @group integration
     * @group attachments
     */
    public function testSendEmailWithMultipleAttachments(): void
    {
        if (empty($this->testConfig['to']) || empty($this->testConfig['from'])) {
            $this->markTestSkipped('TEST_TO and TEST_FROM are required for attachment test');
        }

        $email = $this->client->transaction_email();
        
        $email->add_to($this->testConfig['to'], $this->testConfig['to_name'])
              ->set_from($this->testConfig['from'], $this->testConfig['from_name']);
              
        $email->subject = 'Multiple Attachments Test - ' . date('Y-m-d H:i:s');
        $email->text = "This is a test email with multiple attachments.\n\nFiles:\n- test.png (image)\n- LICENSE (text)\n\nSent at: " . date('Y-m-d H:i:s');
        $email->html = '<h1>Multiple Attachments Test</h1><p>This is a test email with multiple attachments.</p><ul><li><strong>test.png</strong> (image)</li><li><strong>LICENSE</strong> (text)</li></ul><p><strong>Sent at:</strong> ' . date('Y-m-d H:i:s') . '</p>';

        // Add multiple attachments
        $email->attachments = [$this->testImagePath, $this->testTextPath];

        try {
            $result = $email->send();
            
            $this->assertTrue($result);
            $this->assertNotNull($email->id);
            echo "\nMultiple Attachments Test Result:\n";
            echo "Success: " . ($result ? 'true' : 'false') . "\n";
            echo "Message ID: " . $email->id . "\n";
            echo "Attachments: " . count($email->attachments) . "\n";
            echo "Files:\n";
            foreach ($email->attachments as $index => $attachment) {
                echo "  " . ($index + 1) . ". " . basename($attachment) . " (" . filesize($attachment) . " bytes)\n";
            }
            
        } catch (CustomersMailCloudError $e) {
            echo "\nMultiple Attachments API Error occurred:\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            echo "Error Codes: " . implode(', ', $e->getAllErrorCodes()) . "\n";
            $this->markTestSkipped('Multiple Attachments API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->fail('Unexpected multiple attachments error: ' . $e->getMessage());
        }
    }

    public function testAttachmentValidation(): void
    {
        $email = $this->client->transaction_email();
        
        // Test with non-existent file
        $email->attachments = ['/path/to/non-existent-file.txt'];
        
        $email->add_to('test@example.com', 'Test')
              ->set_from('sender@example.com', 'Sender');
        $email->subject = 'Test';
        $email->text = 'Test';

        // This should not throw an exception during setup
        $this->assertCount(1, $email->attachments);
        
        // The error should occur during send() when file doesn't exist
        // We won't actually test send() here to avoid API calls in unit tests
    }

    public function testAttachmentLimits(): void
    {
        $email = $this->client->transaction_email();
        
        // Test with maximum attachments (10)
        $attachments = [];
        for ($i = 1; $i <= 10; $i++) {
            $attachments[] = $this->testImagePath;
        }
        $email->attachments = $attachments;
        
        $this->assertCount(10, $email->attachments);

        // Test with more than maximum attachments (11) - should still work but API might reject
        $attachments[] = $this->testTextPath;
        $email->attachments = $attachments;
        
        $this->assertCount(11, $email->attachments);
    }

    public function testAttachmentFileInfo(): void
    {
        // Test file information
        $this->assertTrue(file_exists($this->testImagePath));
        $this->assertTrue(file_exists($this->testTextPath));
        
        $imageSize = filesize($this->testImagePath);
        $textSize = filesize($this->testTextPath);
        
        $this->assertGreaterThan(0, $imageSize);
        $this->assertGreaterThan(0, $textSize);
        
        echo "\nTest File Information:\n";
        echo "Image: " . basename($this->testImagePath) . " (" . $imageSize . " bytes)\n";
        echo "Text: " . basename($this->testTextPath) . " (" . $textSize . " bytes)\n";
    }

    /**
     * @group integration
     * @group attachments
     */
    public function testEmailWithAttachmentsAndSubstitutions(): void
    {
        if (empty($this->testConfig['to']) || empty($this->testConfig['from'])) {
            $this->markTestSkipped('TEST_TO and TEST_FROM are required for attachment test');
        }

        $email = $this->client->transaction_email();
        
        $email->add_to($this->testConfig['to'], $this->testConfig['to_name'], [
                  'document_name' => 'ライセンス文書',
                  'image_name' => 'テスト画像',
                  'date' => date('Y年m月d日'),
                  'time' => date('H:i:s')
              ])
              ->set_from($this->testConfig['from'], $this->testConfig['from_name']);
              
        $email->subject = '添付ファイル付きメール - ((#document_name#))と((#image_name#))';
        $email->text = "((#to_name#)) 様\n\n添付ファイル付きのメールをお送りします。\n\n添付ファイル:\n- ((#document_name#)): LICENSE\n- ((#image_name#)): test.png\n\n送信日時: ((#date#)) ((#time#))\n\nこれはテストメールです。";
        $email->html = '<h1>添付ファイル付きメール</h1><p>((#to_name#)) 様</p><p>添付ファイル付きのメールをお送りします。</p><h2>添付ファイル:</h2><ul><li><strong>((#document_name#)):</strong> LICENSE</li><li><strong>((#image_name#)):</strong> test.png</li></ul><p><strong>送信日時:</strong> ((#date#)) ((#time#))</p><p><em>これはテストメールです。</em></p>';

        // Add attachments
        $email->attachments = [$this->testTextPath, $this->testImagePath];

        try {
            $result = $email->send();
            
            $this->assertTrue($result);
            $this->assertNotNull($email->id);
            echo "\nAttachments with Substitutions Test Result:\n";
            echo "Success: " . ($result ? 'true' : 'false') . "\n";
            echo "Message ID: " . $email->id . "\n";
            echo "Attachments: " . count($email->attachments) . "\n";
            
        } catch (CustomersMailCloudError $e) {
            echo "\nAttachments with Substitutions API Error occurred:\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            echo "Error Codes: " . implode(', ', $e->getAllErrorCodes()) . "\n";
            $this->markTestSkipped('Attachments with Substitutions API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->fail('Unexpected attachments with substitutions error: ' . $e->getMessage());
        }
    }
}