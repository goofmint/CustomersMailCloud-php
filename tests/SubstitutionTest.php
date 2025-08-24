<?php

declare(strict_types=1);

namespace CustomersMailCloud\Tests;

use CustomersMailCloud\EmailAddress;
use PHPUnit\Framework\TestCase;

class SubstitutionTest extends TestCase
{
    public function testSubstitutionImplementation(): void
    {
        // Test the exact format you want
        $email = new EmailAddress('test@example.com', 'User', [
            'key1' => 'value1',
            'key2' => 'value2'
        ]);
        
        // Get the JSON and decode to see the structure
        $json = $email->to_json();
        $data = json_decode($json, true);
        
        // Verify the exact structure you want
        $expected = [
            'address' => 'test@example.com',
            'name' => 'User',
            'key1' => 'value1',
            'key2' => 'value2'
        ];
        
        $this->assertEquals($expected, $data);
        
        // Also verify individual keys are present and correct
        $this->assertEquals('test@example.com', $data['address']);
        $this->assertEquals('User', $data['name']);
        $this->assertEquals('value1', $data['key1']);
        $this->assertEquals('value2', $data['key2']);
        $this->assertCount(4, $data);
    }
    
    public function testSubstitutionWithoutName(): void
    {
        $email = new EmailAddress('test@example.com', '', [
            'key1' => 'value1',
            'key2' => 'value2'
        ]);
        
        $json = $email->to_json();
        $data = json_decode($json, true);
        
        $expected = [
            'address' => 'test@example.com',
            'name' => '',
            'key1' => 'value1',
            'key2' => 'value2'
        ];
        
        $this->assertEquals($expected, $data);
    }
    
    public function testSubstitutionWithComplexValues(): void
    {
        $email = new EmailAddress('customer@example.com', 'Customer Name', [
            'product_name' => 'Premium Plan',
            'order_id' => 'ORDER-12345',
            'price' => '$99.99',
            'url' => 'https://example.com/order/12345'
        ]);
        
        $json = $email->to_json();
        $data = json_decode($json, true);
        
        $expected = [
            'address' => 'customer@example.com',
            'name' => 'Customer Name',
            'product_name' => 'Premium Plan',
            'order_id' => 'ORDER-12345',
            'price' => '$99.99',
            'url' => 'https://example.com/order/12345'
        ];
        
        $this->assertEquals($expected, $data);
        
        // Verify the JSON doesn't have escaped slashes
        $this->assertStringContainsString('https://example.com/order/12345', $json);
        $this->assertStringNotContainsString('https:\/\/', $json);
    }
    
    public function testAddToMethodWithSubstitutions(): void
    {
        // Test the add_to method directly
        $client = new \CustomersMailCloud\CustomersMailCloud('test', 'test');
        $email = $client->transaction_email();
        
        $email->add_to('test@example.com', 'User', [
            'key1' => 'value1',
            'key2' => 'value2'
        ]);
        
        $this->assertCount(1, $email->to);
        
        $emailAddress = $email->to[0];
        $this->assertInstanceOf(EmailAddress::class, $emailAddress);
        
        $json = $emailAddress->to_json();
        $data = json_decode($json, true);
        
        $expected = [
            'address' => 'test@example.com',
            'name' => 'User',
            'key1' => 'value1',
            'key2' => 'value2'
        ];
        
        $this->assertEquals($expected, $data);
    }
}