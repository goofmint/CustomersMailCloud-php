# CustomersMailCloud PHP SDK

PHP SDK for CustomersMailCloud API - Send transactional emails with ease.

## Installation

```bash
composer require customers-mail-cloud/php-sdk
```

## Quick Start

```php
<?php
require_once 'vendor/autoload.php';

use CustomersMailCloud\CustomersMailCloud;

// Initialize client
$client = new CustomersMailCloud($api_user, $api_key);

// Create and send email
$email = $client->transaction_email();

$email->add_to('recipient@example.com', 'Recipient Name')
      ->set_from('sender@example.com', 'Sender Name');

$email->subject = 'Test Email';
$email->text = 'This is a test email.';
$email->html = '<p>This is a <strong>test email</strong>.</p>';

try {
    $success = $email->send();
    if ($success) {
        echo "Email sent! ID: " . $email->id;
    }
} catch (CustomersMailCloudError $e) {
    echo "API Error: " . $e->getMessage();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Features

- **Simple API**: Easy-to-use fluent interface
- **Substitution Variables**: Support for personalized emails with `((#variable#))` syntax
- **Multiple Recipients**: Send to multiple recipients with individual substitutions
- **Attachments**: Support for file attachments (up to 10 files)
- **Error Handling**: Comprehensive error handling with detailed error information
- **Sub-domain Support**: Configurable sub-domain for different environments

## Configuration

### Basic Setup

```php
$client = new CustomersMailCloud($api_user, $api_key);

// Optional: Set sub-domain (default: 'sandbox')
$client->sub_domain = 'your-subdomain';
```

### Environment Variables

Create a `.env` file:

```env
API_USER=your_api_user
API_KEY=your_api_key
```

## Usage Examples

### Basic Email

```php
$email = $client->transaction_email();

$email->add_to('user@example.com', 'User Name')
      ->set_from('sender@example.com', 'Sender Name')
      ->set_reply_to('reply@example.com', 'Reply Name');

$email->subject = 'Welcome!';
$email->text = 'Welcome to our service!';
$email->html = '<h1>Welcome!</h1><p>Welcome to our service!</p>';

$success = $email->send();
```

### Email with Substitution Variables

```php
$email = $client->transaction_email();

$email->add_to('customer@example.com', 'Customer', [
    'product_name' => 'Premium Plan',
    'order_id' => 'ORDER-12345',
    'customer_name' => 'John Doe'
]);

$email->subject = 'Order Confirmation: ((#product_name#))';
$email->text = 'Hello ((#customer_name#)), your order ((#order_id#)) has been confirmed.';
$email->html = '<p>Hello <strong>((#customer_name#))</strong>, your order <em>((#order_id#))</em> has been confirmed.</p>';

$success = $email->send();
```

### Email with Attachments

```php
$email = $client->transaction_email();

$email->add_to('recipient@example.com', 'Recipient')
      ->set_from('sender@example.com', 'Sender');

$email->subject = 'Documents Attached';
$email->text = 'Please find the attached documents.';
$email->attachments = [
    '/path/to/document.pdf',
    '/path/to/image.jpg'
];

$success = $email->send();
```

## Error Handling

The SDK provides comprehensive error handling:

```php
try {
    $success = $email->send();
} catch (CustomersMailCloudError $e) {
    // API-specific errors (DKIM, validation, etc.)
    echo "API Error: " . $e->getMessage();
    echo "Error Code: " . $e->getErrorCode();
    
    // Check for specific error codes
    if ($e->hasErrorCode('13-006')) {
        echo "DKIM setup required!";
    }
    
    // Get detailed error information
    $errorInfo = $e->getErrorInfo();
    
} catch (Exception $e) {
    // General errors (network, file not found, etc.)
    echo "General Error: " . $e->getMessage();
}
```

## Testing

Run the test suite:

```bash
# All tests
composer test

# Integration tests (requires API credentials)
vendor/bin/phpunit --group integration

# Attachment tests
vendor/bin/phpunit --group attachments

# Error handling tests
vendor/bin/phpunit --group error-handling
```

## Examples

Check the `examples/` directory for complete working examples:

- `examples/send_email.php` - Basic email sending
- `examples/send_email_with_attachments.php` - Email with attachments

## Requirements

- PHP 7.4 or higher
- Guzzle HTTP client
- JSON extension

## License

MIT License. See LICENSE file for details.

## Support

For issues and questions, please use the GitHub issue tracker.