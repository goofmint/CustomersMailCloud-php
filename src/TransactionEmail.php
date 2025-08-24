<?php

declare(strict_types=1);

namespace CustomersMailCloud;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

/**
 * TransactionEmail class for handling transactional emails
 */
class TransactionEmail
{
    public array $to = [];
    public ?EmailAddress $from = null;
    public string $subject = '';
    public string $text = '';
    public string $html = '';
    public ?EmailAddress $reply_to = null;
    public array $cc = [];
    public array $bcc = [];
    public array $headers = [];
    public string $charset = 'UTF-8';
    public string $envfrom = '';
    public array $attachments = [];
    public ?string $id = null;

    private CustomersMailCloud $client;

    /**
     * TransactionEmail constructor.
     *
     * @param CustomersMailCloud $client The CustomersMailCloud client instance
     */
    public function __construct(CustomersMailCloud $client)
    {
        $this->client = $client;
    }

    /**
     * Add a recipient to the email
     *
     * @param string $address Email address
     * @param string $name Display name (optional)
     * @param array|null $substitutions Substitution variables (optional)
     * @return self
     */
    public function add_to(string $address, string $name = '', ?array $substitutions = null): self
    {
        $this->to[] = new EmailAddress($address, $name, $substitutions);
        return $this;
    }

    /**
     * Set the sender of the email
     *
     * @param string $address Email address
     * @param string $name Display name (optional)
     * @return self
     */
    public function set_from(string $address, string $name = ''): self
    {
        $this->from = new EmailAddress($address, $name);
        return $this;
    }

    /**
     * Set the reply-to address of the email
     *
     * @param string $address Email address
     * @param string $name Display name (optional)
     * @return self
     */
    public function set_reply_to(string $address, string $name = ''): self
    {
        $this->reply_to = new EmailAddress($address, $name);
        return $this;
    }

    /**
     * Send the email via the API
     *
     * @return bool True if email was sent successfully
     * @throws CustomersMailCloudError If API returns errors
     * @throws \Exception For other errors (network, etc.)
     */
    public function send(): bool
    {
        // Validate required fields
        if (empty($this->to)) {
            throw new \Exception('At least one recipient is required');
        }
        if ($this->from === null) {
            throw new \Exception('Sender is required');
        }
        if (empty($this->subject)) {
            throw new \Exception('Subject is required');
        }
        if (empty($this->text) && empty($this->html)) {
            throw new \Exception('Either text or html content is required');
        }

        // Validate attachments
        if (!empty($this->attachments)) {
            if (count($this->attachments) > 10) {
                throw new \Exception('Maximum 10 attachments are allowed');
            }
            foreach ($this->attachments as $attachment) {
                if (!file_exists($attachment)) {
                    throw new \Exception("Attachment file not found: " . $attachment);
                }
                if (!is_readable($attachment)) {
                    throw new \Exception("Attachment file is not readable: " . $attachment);
                }
            }
        }

        // Build endpoint URL
        $url = sprintf('https://%s.smtps.jp/api/v2/emails/send.json', $this->client->sub_domain);

        // Prepare data
        $data = [
            'api_user' => $this->client->getApiUser(),
            'api_key' => $this->client->getApiKey(),
            'subject' => $this->subject,
            'text' => $this->text,
            'html' => $this->html,
            'charset' => $this->charset,
            'envfrom' => $this->envfrom,
        ];

        // Add 'to' recipients
        $toArray = [];
        foreach ($this->to as $recipient) {
            $toArray[] = json_decode($recipient->to_json(), true);
        }
        $data['to'] = json_encode($toArray);

        // Add 'from'
        $data['from'] = $this->from->to_json();

        // Add 'reply_to' if set
        if ($this->reply_to !== null) {
            $data['replyto'] = $this->reply_to->to_json();
        }

        // Add 'cc' if set
        if (!empty($this->cc)) {
            $ccArray = [];
            foreach ($this->cc as $ccEmail) {
                $ccArray[] = ['address' => $ccEmail];
            }
            $data['cc'] = json_encode($ccArray);
        }

        // Add 'bcc' if set
        if (!empty($this->bcc)) {
            $bccArray = [];
            foreach ($this->bcc as $bccEmail) {
                $bccArray[] = ['address' => $bccEmail];
            }
            $data['bcc'] = json_encode($bccArray);
        }

        // Add 'headers' if set
        if (!empty($this->headers)) {
            $headersArray = [];
            foreach ($this->headers as $name => $value) {
                $headersArray[] = ['name' => $name, 'value' => $value];
            }
            $data['headers'] = json_encode($headersArray);
        }

        // Prepare request based on attachments
        $httpClient = new GuzzleClient();
        
        try {
            if (!empty($this->attachments)) {
                // Use multipart/form-data for attachments
                $multipart = [];
                
                // Add regular fields
                foreach ($data as $key => $value) {
                    if (!empty($value)) {
                        $multipart[] = [
                            'name' => $key,
                            'contents' => $value
                        ];
                    }
                }
                
                // Add attachments
                $data['attachments'] = count($this->attachments);
                $multipart[] = [
                    'name' => 'attachments',
                    'contents' => (string) count($this->attachments)
                ];
                
                foreach ($this->attachments as $index => $attachment) {
                    $attachmentKey = 'attachment' . ($index + 1);
                    if (file_exists($attachment)) {
                        $multipart[] = [
                            'name' => $attachmentKey,
                            'contents' => fopen($attachment, 'r'),
                            'filename' => basename($attachment)
                        ];
                    } else {
                        throw new \Exception("Attachment file not found: " . $attachment);
                    }
                }
                
                $response = $httpClient->post($url, [
                    'multipart' => $multipart
                ]);
            } else {
                // Use application/json for regular emails
                $response = $httpClient->post($url, [
                    'json' => $data,
                    'headers' => [
                        'Content-Type' => 'application/json'
                    ]
                ]);
            }

            $body = $response->getBody()->getContents();
            $responseData = json_decode($body, true);
            
            // Check for API errors
            if (isset($responseData['errors']) && !empty($responseData['errors'])) {
                throw new CustomersMailCloudError($responseData['errors'], $responseData);
            }
            
            // Success - extract message ID
            if (isset($responseData['id'])) {
                $this->id = $responseData['id'];
                return true;
            }
            
            // Unexpected response format
            throw new \Exception('Unexpected API response format: ' . $body);
            
        } catch (ClientException $e) {
            // Handle 4xx client errors (like 400 Bad Request)
            $response = $e->getResponse();
            if ($response) {
                $body = $response->getBody()->getContents();
                $responseData = json_decode($body, true);
                
                if (isset($responseData['errors']) && !empty($responseData['errors'])) {
                    throw new CustomersMailCloudError($responseData['errors'], $responseData);
                }
            }
            throw new \Exception('Client error: ' . $e->getMessage());
            
        } catch (ServerException $e) {
            // Handle 5xx server errors
            throw new \Exception('Server error: ' . $e->getMessage());
            
        } catch (GuzzleException $e) {
            // Handle other Guzzle exceptions (network errors, etc.)
            throw new \Exception('Failed to send email: ' . $e->getMessage());
        }
    }
}