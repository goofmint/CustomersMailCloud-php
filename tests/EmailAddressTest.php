<?php

declare(strict_types=1);

namespace CustomersMailCloud\Tests;

use CustomersMailCloud\EmailAddress;
use PHPUnit\Framework\TestCase;

class EmailAddressTest extends TestCase
{
    public function testCanCreateEmailAddress(): void
    {
        $address = 'test@example.com';
        $name = 'Test User';
        
        $email = new EmailAddress($address, $name);
        
        $this->assertEquals($address, $email->address);
        $this->assertEquals($name, $email->name);
        $this->assertNull($email->substitutions);
    }
    
    public function testCanCreateEmailAddressWithSubstitutions(): void
    {
        $address = 'test@example.com';
        $name = 'Test User';
        $substitutions = ['key1' => 'value1', 'key2' => 'value2'];
        
        $email = new EmailAddress($address, $name, $substitutions);
        
        $this->assertEquals($address, $email->address);
        $this->assertEquals($name, $email->name);
        $this->assertEquals($substitutions, $email->substitutions);
    }
    
    public function testCanCreateEmailAddressWithOnlyAddress(): void
    {
        $address = 'test@example.com';
        
        $email = new EmailAddress($address);
        
        $this->assertEquals($address, $email->address);
        $this->assertEquals('', $email->name);
        $this->assertNull($email->substitutions);
    }

    public function testToJsonWithNameOnly(): void
    {
        $email = new EmailAddress('user1@example.com', '山田太郎');
        
        $json = $email->to_json();
        $data = json_decode($json, true);
        
        $this->assertEquals('user1@example.com', $data['address']);
        $this->assertEquals('山田太郎', $data['name']);
        $this->assertCount(2, $data);
    }

    public function testToJsonWithSubstitutions(): void
    {
        $substitutions = [
            'item1' => '商品1：http://example.com/sale/item1.html',
            'item2' => '商品2：http://example.com/sale/item2.html',
            'customer-id' => 'CID0001'
        ];
        
        $email = new EmailAddress('user1@example.com', '山田太郎', $substitutions);
        
        $json = $email->to_json();
        $data = json_decode($json, true);
        
        $this->assertEquals('user1@example.com', $data['address']);
        $this->assertEquals('山田太郎', $data['name']);
        $this->assertEquals('商品1：http://example.com/sale/item1.html', $data['item1']);
        $this->assertEquals('商品2：http://example.com/sale/item2.html', $data['item2']);
        $this->assertEquals('CID0001', $data['customer-id']);
        $this->assertCount(5, $data);
    }

    public function testToJsonWithoutName(): void
    {
        $email = new EmailAddress('user1@example.com');
        
        $json = $email->to_json();
        $data = json_decode($json, true);
        
        $this->assertEquals('user1@example.com', $data['address']);
        $this->assertEquals('', $data['name']);
        $this->assertCount(2, $data);
    }

    public function testToJsonPreservesUnicodeAndSlashes(): void
    {
        $substitutions = [
            'url' => 'https://example.com/path/to/page',
            'japanese' => '日本語のテキスト'
        ];
        
        $email = new EmailAddress('user@example.com', '田中花子', $substitutions);
        
        $json = $email->to_json();
        
        // JSONにUnicodeエスケープやスラッシュのエスケープがないことを確認
        $this->assertStringContainsString('田中花子', $json);
        $this->assertStringContainsString('日本語のテキスト', $json);
        $this->assertStringContainsString('https://example.com/path/to/page', $json);
        $this->assertStringNotContainsString('\\u', $json);
        $this->assertStringNotContainsString('\\/', $json);
    }
}