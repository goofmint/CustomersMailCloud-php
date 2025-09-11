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
- **Delivery & Bounce Tracking**: Retrieve delivery status and bounce information
- **Statistics**: Get email sending statistics and analytics
- **Audit Logs**: Access login logs and operation logs for security monitoring

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

### Statistics API

```php
// Get monthly statistics
$statistics = $client->statistics([
    'year' => 2024,
    'month' => 1,
    'server_composition' => 'your-server-composition'
]);

foreach ($statistics as $stat) {
    echo "Date: " . $stat->date . "\n";
    echo "Queued: " . $stat->queued . "\n";
    echo "Succeeded: " . $stat->succeeded . "\n";
    echo "Failed: " . $stat->failed . "\n";
    echo "Blocked: " . $stat->blocked . "\n";
    echo "Valid: " . $stat->valid . "\n";
}

// Get total statistics for a month
$totalStats = $client->statistics([
    'year' => 2024,
    'month' => 1,
    'server_composition' => 'your-server-composition',
    'total' => true
]);
```

### Delivery Tracking API

```php
// Get delivery status for a specific date
$deliveries = $client->deliveries([
    'server_composition' => 'your-server-composition',
    'date' => '2024-01-15'
]);

foreach ($deliveries as $delivery) {
    echo "Subject: " . $delivery->subject . "\n";
    echo "Status: " . $delivery->status . "\n";
    echo "From: " . $delivery->from . "\n";
    echo "To: " . $delivery->to . "\n";
}

// With filters
$filteredDeliveries = $client->deliveries([
    'server_composition' => 'your-server-composition',
    'date' => '2024-01-15',
    'status' => 'succeeded',
    'from' => 'sender@example.com'
]);
```

### Bounce Information API

```php
// Get bounce information
$bounces = $client->bounces([
    'server_composition' => 'your-server-composition',
    'start_date' => '2024-01-01',
    'end_date' => '2024-01-31'
]);

foreach ($bounces as $bounce) {
    echo "Subject: " . $bounce->subject . "\n";
    echo "Status: " . $bounce->status . "\n";
    echo "From: " . $bounce->from . "\n";
    echo "To: " . $bounce->to . "\n";
    echo "Reason: " . $bounce->reason . "\n";
}
```

### Audit Logs API

```php
// Get login logs
$loginLogs = $client->auditlogs([
    'type' => 'login',
    'start_date' => '2024-01-01',
    'end_date' => '2024-01-31'
]);

foreach ($loginLogs as $log) {
    echo "Created: " . $log->created . "\n";
    echo "Account: " . $log->account . "\n";
    echo "IP Address: " . $log->ipaddress . "\n";
    echo "Result: " . $log->result . "\n";
    echo "Reason: " . $log->reason . "\n";
}

// Get operation logs with filters
$operationLogs = $client->auditlogs([
    'type' => 'operation',
    'account' => 'admin',
    'start_date' => '2024-01-01',
    'end_date' => '2024-01-31',
    'p' => 0,  // Page number
    'r' => 10  // Records per page
]);

foreach ($operationLogs as $log) {
    echo "Created: " . $log->created . "\n";
    echo "Account: " . $log->account . "\n";
    echo "Name: " . $log->name . "\n";
    echo "Function: " . $log->function . "\n";
    echo "Operation: " . $log->operation . "\n";
}

// Download audit logs as ZIP file
use CustomersMailCloud\Auditlog;

$zipContent = Auditlog::download($client, [
    'type' => 'login',
    'start_date' => '2024-01-01',
    'end_date' => '2024-01-31'
]);

// Save ZIP file
file_put_contents('auditlogs-' . date('YmdHis') . '.zip', $zipContent);
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

# Statistics tests
vendor/bin/phpunit --group statistics

# Delivery tests
vendor/bin/phpunit --group deliveries

# Audit logs tests
vendor/bin/phpunit --group auditlogs
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