<?php

declare(strict_types=1);

namespace CustomersMailCloud\Tests;

use CustomersMailCloud\CustomersMailCloud;
use CustomersMailCloud\TransactionEmail;
use CustomersMailCloud\EmailAddress;
use PHPUnit\Framework\TestCase;

class TransactionEmailTest extends TestCase
{
    private CustomersMailCloud $client;

    protected function setUp(): void
    {
        $this->client = new CustomersMailCloud('test-user', 'test-key');
    }

    public function testCanCreateTransactionEmail(): void
    {
        $email = new TransactionEmail($this->client);
        
        $this->assertInstanceOf(TransactionEmail::class, $email);
        $this->assertEquals([], $email->to);
        $this->assertNull($email->from);
        $this->assertEquals('', $email->subject);
        $this->assertEquals('', $email->text);
        $this->assertEquals('', $email->html);
        $this->assertNull($email->reply_to);
        $this->assertEquals([], $email->cc);
        $this->assertEquals([], $email->bcc);
        $this->assertEquals([], $email->headers);
        $this->assertEquals('UTF-8', $email->charset);
        $this->assertEquals('', $email->envfrom);
        $this->assertEquals([], $email->attachments);
    }

    public function testCanSetProperties(): void
    {
        $email = new TransactionEmail($this->client);
        
        $toEmail = new EmailAddress('to@example.com', 'To User');
        $fromEmail = new EmailAddress('from@example.com', 'From User');
        $replyToEmail = new EmailAddress('reply@example.com', 'Reply User');
        
        $email->to = [$toEmail];
        $email->from = $fromEmail;
        $email->subject = 'Test Subject';
        $email->text = 'Test text content';
        $email->html = '<p>Test HTML content</p>';
        $email->reply_to = $replyToEmail;
        $email->cc = ['cc@example.com'];
        $email->bcc = ['bcc@example.com'];
        $email->headers = ['X-Custom-Header' => 'value'];
        $email->charset = 'ISO-8859-1';
        $email->envfrom = 'env@example.com';
        $email->attachments = ['file1.pdf', 'file2.jpg'];
        
        $this->assertEquals([$toEmail], $email->to);
        $this->assertEquals($fromEmail, $email->from);
        $this->assertEquals('Test Subject', $email->subject);
        $this->assertEquals('Test text content', $email->text);
        $this->assertEquals('<p>Test HTML content</p>', $email->html);
        $this->assertEquals($replyToEmail, $email->reply_to);
        $this->assertEquals(['cc@example.com'], $email->cc);
        $this->assertEquals(['bcc@example.com'], $email->bcc);
        $this->assertEquals(['X-Custom-Header' => 'value'], $email->headers);
        $this->assertEquals('ISO-8859-1', $email->charset);
        $this->assertEquals('env@example.com', $email->envfrom);
        $this->assertEquals(['file1.pdf', 'file2.jpg'], $email->attachments);
    }

    public function testAddToMethod(): void
    {
        $email = new TransactionEmail($this->client);
        
        $email->add_to('test1@example.com', 'Test User 1');
        $email->add_to('test2@example.com', 'Test User 2', ['key' => 'value']);
        $email->add_to('test3@example.com');
        
        $this->assertCount(3, $email->to);
        $this->assertEquals('test1@example.com', $email->to[0]->address);
        $this->assertEquals('Test User 1', $email->to[0]->name);
        $this->assertNull($email->to[0]->substitutions);
        
        $this->assertEquals('test2@example.com', $email->to[1]->address);
        $this->assertEquals('Test User 2', $email->to[1]->name);
        $this->assertEquals(['key' => 'value'], $email->to[1]->substitutions);
        
        $this->assertEquals('test3@example.com', $email->to[2]->address);
        $this->assertEquals('', $email->to[2]->name);
        $this->assertNull($email->to[2]->substitutions);
    }

    public function testSetFromMethod(): void
    {
        $email = new TransactionEmail($this->client);
        
        $email->set_from('from@example.com', 'From User');
        
        $this->assertNotNull($email->from);
        $this->assertEquals('from@example.com', $email->from->address);
        $this->assertEquals('From User', $email->from->name);
        $this->assertNull($email->from->substitutions);
    }

    public function testSetReplyToMethod(): void
    {
        $email = new TransactionEmail($this->client);
        
        $email->set_reply_to('reply@example.com', 'Reply User');
        
        $this->assertNotNull($email->reply_to);
        $this->assertEquals('reply@example.com', $email->reply_to->address);
        $this->assertEquals('Reply User', $email->reply_to->name);
        $this->assertNull($email->reply_to->substitutions);
    }

    public function testMethodChaining(): void
    {
        $email = new TransactionEmail($this->client);
        
        $result = $email
            ->add_to('to@example.com', 'To User')
            ->set_from('from@example.com', 'From User')
            ->set_reply_to('reply@example.com', 'Reply User');
        
        $this->assertSame($email, $result);
        $this->assertCount(1, $email->to);
        $this->assertNotNull($email->from);
        $this->assertNotNull($email->reply_to);
    }

    public function testIdPropertyInitiallyNull(): void
    {
        $email = new TransactionEmail($this->client);
        
        $this->assertNull($email->id);
    }
}