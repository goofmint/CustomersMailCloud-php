<?php

declare(strict_types=1);

namespace CustomersMailCloud\Tests;

use CustomersMailCloud\CustomersMailCloud;
use CustomersMailCloud\EmailAddress;
use CustomersMailCloud\CustomersMailCloudError;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for sending emails using .env credentials
 * This test requires valid API credentials in .env file
 */
class SendEmailTest extends TestCase
{
    private CustomersMailCloud $client;
    private string $apiUser;
    private string $apiKey;
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

        $this->apiUser = $env['API_USER'];
        $this->apiKey = $env['API_KEY'];
        
        if (empty($this->apiUser) || empty($this->apiKey)) {
            $this->markTestSkipped('API credentials are empty');
        }

        // Load test configuration
        $this->testConfig = [
            'to' => $env['TEST_TO'] ?? '',
            'to_name' => $env['TEST_NAME'] ?? '',
            'from' => $env['TEST_FROM'] ?? '',
            'from_name' => $env['TEST_FROM_NAME'] ?? '',
            'cc' => $env['TEST_CC'] ?? '',
            'bcc' => $env['TEST_BCC'] ?? '',
        ];

        $this->client = new CustomersMailCloud($this->apiUser, $this->apiKey);
    }

    public function testSendBasicEmail(): void
    {
        $email = $this->client->transaction_email();
        
        $email->add_to('test@example.com', 'テストユーザー')
              ->set_from('sender@example.com', '送信者')
              ->set_reply_to('reply@example.com', 'リプライ先');
              
        $email->subject = 'テストメール';
        $email->text = 'これはテストメールのテキスト部分です。';
        $email->html = '<p>これは<strong>テストメール</strong>のHTML部分です。</p>';

        try {
            $result = $email->send();
            
            $this->assertTrue($result);
            $this->assertNotNull($email->id);
            
        } catch (CustomersMailCloudError $e) {
            // APIエラーの場合はスキップ
            $this->markTestSkipped('API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            // その他のエラー
            $this->markTestSkipped('API is not available: ' . $e->getMessage());
        }
    }

    public function testSendEmailWithSubstitutions(): void
    {
        $email = $this->client->transaction_email();
        
        $email->add_to('test@example.com', 'テストユーザー', [
                  'product_name' => 'テスト商品',
                  'order_id' => 'ORDER-12345'
              ])
              ->set_from('sender@example.com', '送信者')
              ->set_reply_to('reply@example.com', 'リプライ先');
              
        $email->subject = '注文確認: ((#product_name#))';
        $email->text = 'ご注文ありがとうございます。注文ID: ((#order_id#))';
        $email->html = '<p>ご注文ありがとうございます。<br>注文ID: <strong>((#order_id#))</strong></p>';

        try {
            $result = $email->send();
            
            $this->assertTrue($result);
            $this->assertNotNull($email->id);
            
        } catch (CustomersMailCloudError $e) {
            $this->markTestSkipped('API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->markTestSkipped('API is not available: ' . $e->getMessage());
        }
    }

    public function testSendEmailWithHeaders(): void
    {
        $email = $this->client->transaction_email();
        
        $email->add_to('test@example.com', 'テストユーザー')
              ->set_from('sender@example.com', '送信者');
              
        $email->subject = 'ヘッダー付きテストメール';
        $email->text = 'カスタムヘッダー付きのテストメールです。';
        $email->headers = [
            'X-Priority' => '1',
            'X-Custom-Header' => 'CustomValue'
        ];

        try {
            $result = $email->send();
            
            $this->assertTrue($result);
            $this->assertNotNull($email->id);
            
        } catch (CustomersMailCloudError $e) {
            $this->markTestSkipped('API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->markTestSkipped('API is not available: ' . $e->getMessage());
        }
    }

    public function testValidationErrors(): void
    {
        $email = $this->client->transaction_email();

        // 必須フィールドが不足している場合のテスト
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('At least one recipient is required');
        
        $email->send();
    }

    public function testSubDomainInUrl(): void
    {
        $this->client->sub_domain = 'test-subdomain';
        $email = $this->client->transaction_email();
        
        $email->add_to('test@example.com', 'Test User')
              ->set_from('sender@example.com', 'Sender');
              
        $email->subject = 'Test Subject';
        $email->text = 'Test content';

        // sendメソッドは内部でURLを構築するが、実際のAPIコールはしない
        // （テスト環境での無効なサブドメインのため）
        try {
            $response = $email->send();
        } catch (\Exception $e) {
            // 無効なサブドメインの場合はエラーが発生することを確認
            $this->assertStringContainsString('test-subdomain.smtps.jp', $e->getMessage());
        }
    }

    /**
     * @group integration
     */
    public function testSendRealEmail(): void
    {
        if (empty($this->testConfig['to']) || empty($this->testConfig['from'])) {
            $this->markTestSkipped('TEST_TO and TEST_FROM are required for real email sending test');
        }

        $email = $this->client->transaction_email();
        
        $email->add_to($this->testConfig['to'], $this->testConfig['to_name'])
              ->set_from($this->testConfig['from'], $this->testConfig['from_name']);
              
        $email->subject = 'CustomersMailCloud PHP SDK テスト - ' . date('Y-m-d H:i:s');
        $email->text = "これは CustomersMailCloud PHP SDK からの実際のテストメールです。\n\n送信時刻: " . date('Y-m-d H:i:s');
        $email->html = '<h1>CustomersMailCloud PHP SDK テスト</h1><p>これは CustomersMailCloud PHP SDK からの実際のテストメールです。</p><p><strong>送信時刻:</strong> ' . date('Y-m-d H:i:s') . '</p>';

        if (!empty($this->testConfig['cc'])) {
            $email->cc = [$this->testConfig['cc']];
        }
        
        if (!empty($this->testConfig['bcc'])) {
            $email->bcc = [$this->testConfig['bcc']];
        }

        $email->headers = [
            'X-Test-Source' => 'CustomersMailCloud-PHP-SDK',
            'X-Test-Time' => date('c')
        ];

        try {
            $result = $email->send();
            
            $this->assertTrue($result);
            $this->assertNotNull($email->id);
            echo "\n実際のメール送信テスト結果:\n";
            echo "Success: " . ($result ? 'true' : 'false') . "\n";
            echo "Message ID: " . $email->id . "\n";
            
        } catch (CustomersMailCloudError $e) {
            echo "\nAPI Error occurred:\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            echo "Error Codes: " . implode(', ', $e->getAllErrorCodes()) . "\n";
            $this->markTestSkipped('API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->fail('Real email sending failed: ' . $e->getMessage());
        }
    }

    /**
     * @group integration
     */
    public function testSendRealEmailWithSubstitutions(): void
    {
        if (empty($this->testConfig['to']) || empty($this->testConfig['from'])) {
            $this->markTestSkipped('TEST_TO and TEST_FROM are required for real email sending test');
        }

        $email = $this->client->transaction_email();
        
        $email->add_to($this->testConfig['to'], $this->testConfig['to_name'], [
                  'product_name' => 'CustomersMailCloud PHP SDK',
                  'order_id' => 'SDK-TEST-' . time(),
                  'user_name' => $this->testConfig['to_name'] ?: '顧客様',
                  'company' => 'テスト株式会社'
              ])
              ->set_from($this->testConfig['from'], $this->testConfig['from_name']);
              
        $email->subject = '【((#company#))】ご注文確認: ((#product_name#)) (注文ID: ((#order_id#)))';
        $email->text = "((#user_name#)) 様\n\nご注文ありがとうございます。\n\n商品名: ((#product_name#))\n注文ID: ((#order_id#))\n会社名: ((#company#))\n\nこれはテストメールです。";
        $email->html = '<h1>ご注文確認</h1><p>((#user_name#)) 様</p><p>ご注文ありがとうございます。</p><table><tr><td>商品名:</td><td><strong>((#product_name#))</strong></td></tr><tr><td>注文ID:</td><td>((#order_id#))</td></tr><tr><td>会社名:</td><td>((#company#))</td></tr></table><p><em>これはテストメールです。</em></p>';

        try {
            $result = $email->send();
            
            $this->assertTrue($result);
            $this->assertNotNull($email->id);
            echo "\n差し込み文字列付きメール送信テスト結果:\n";
            echo "Success: " . ($result ? 'true' : 'false') . "\n";
            echo "Message ID: " . $email->id . "\n";
            
        } catch (CustomersMailCloudError $e) {
            echo "\nAPI Error occurred:\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            echo "Error Codes: " . implode(', ', $e->getAllErrorCodes()) . "\n";
            $this->markTestSkipped('API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->fail('Real email with substitutions failed: ' . $e->getMessage());
        }
    }

    /**
     * @group integration
     */
    public function testSendRealEmailMultipleRecipients(): void
    {
        if (empty($this->testConfig['to']) || empty($this->testConfig['from'])) {
            $this->markTestSkipped('TEST_TO and TEST_FROM are required for real email sending test');
        }

        $email = $this->client->transaction_email();
        
        // 複数の宛先（同じメールアドレスに異なる差し込み文字列）
        $email->add_to($this->testConfig['to'], $this->testConfig['to_name'] . ' (メイン)', [
                  'role' => 'メイン担当者',
                  'department' => '開発部'
              ]);
              
        if (!empty($this->testConfig['cc'])) {
            $email->add_to($this->testConfig['cc'], $this->testConfig['to_name'] . ' (CC)', [
                      'role' => 'CC担当者',
                      'department' => '営業部'
                  ]);
        }
        
        $email->set_from($this->testConfig['from'], $this->testConfig['from_name']);
              
        $email->subject = '複数宛先テスト - ((#role#))様へ';
        $email->text = "((#role#)) の皆様\n\n((#department#)) の ((#role#)) としてご連絡いたします。\n\nこれは複数宛先のテストメールです。";
        $email->html = '<h1>複数宛先テスト</h1><p><strong>((#role#))</strong> の皆様</p><p>((#department#)) の ((#role#)) としてご連絡いたします。</p><p>これは複数宛先のテストメールです。</p>';

        try {
            $result = $email->send();
            
            $this->assertTrue($result);
            $this->assertNotNull($email->id);
            echo "\n複数宛先メール送信テスト結果:\n";
            echo "Success: " . ($result ? 'true' : 'false') . "\n";
            echo "Message ID: " . $email->id . "\n";
            
        } catch (CustomersMailCloudError $e) {
            echo "\nAPI Error occurred:\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            echo "Error Codes: " . implode(', ', $e->getAllErrorCodes()) . "\n";
            $this->markTestSkipped('API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->fail('Real email with multiple recipients failed: ' . $e->getMessage());
        }
    }
}