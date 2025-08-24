<?php

declare(strict_types=1);

namespace CustomersMailCloud\Tests;

use CustomersMailCloud\CustomersMailCloud;
use CustomersMailCloud\Client;
use CustomersMailCloud\TransactionEmail;
use PHPUnit\Framework\TestCase;

class CustomersMailCloudTest extends TestCase
{
    public function testCanInstantiateCustomersMailCloud(): void
    {
        $apiUser = 'test-user';
        $apiKey = 'test-key';
        
        $client = new CustomersMailCloud($apiUser, $apiKey);
        
        $this->assertInstanceOf(CustomersMailCloud::class, $client);
    }
    
    public function testCanInstantiateWithoutConfig(): void
    {
        $apiUser = 'test-user';
        $apiKey = 'test-key';
        
        $client = new CustomersMailCloud($apiUser, $apiKey);
        
        $this->assertInstanceOf(CustomersMailCloud::class, $client);
    }

    public function testApiUserAndKeyAreSetCorrectly(): void
    {
        $apiUser = 'my-api-user';
        $apiKey = 'my-api-key';
        
        $client = new CustomersMailCloud($apiUser, $apiKey);
        
        $this->assertEquals($apiUser, $client->getApiUser());
        $this->assertEquals($apiKey, $client->getApiKey());
    }

    public function testSubDomainDefaultsToSandbox(): void
    {
        $client = new CustomersMailCloud('test-user', 'test-key');
        
        $this->assertEquals('sandbox', $client->sub_domain);
    }

    public function testSubDomainCanBeModified(): void
    {
        $client = new CustomersMailCloud('test-user', 'test-key');
        $client->sub_domain = 'aaa';
        
        $this->assertEquals('aaa', $client->sub_domain);
    }

    public function testTransactionEmailReturnsTransactionEmailInstance(): void
    {
        $client = new CustomersMailCloud('test-user', 'test-key');
        
        $transactionEmail = $client->transaction_email();
        
        $this->assertInstanceOf(TransactionEmail::class, $transactionEmail);
    }
}