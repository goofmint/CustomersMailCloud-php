<?php

declare(strict_types=1);

namespace CustomersMailCloud;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

/**
 * Auditlog class for handling audit log operations
 */
class Auditlog
{
    public string $created;
    public string $account;
    
    // Login log specific properties
    public string $ipaddress;
    public string $code;
    public string $result;
    public string $reason;
    
    // Operation log specific properties
    public string $name;
    public string $function;
    public string $operation;

    /**
     * Auditlog constructor.
     *
     * @param array $data Auditlog data from API response
     */
    public function __construct(array $data = [])
    {
        $this->sets($data);
    }

    public function sets(array $data = [])
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function set(string $key, $value): void
    {
        switch (strtolower($key)) {
            case 'created':
                $this->created = $value;
                break;
            case 'account':
                $this->account = $value;
                break;
            // Login log properties
            case 'ipaddress':
                $this->ipaddress = $value;
                break;
            case 'code':
                $this->code = $value;
                break;
            case 'result':
                $this->result = $value;
                break;
            case 'reason':
                $this->reason = $value;
                break;
            // Operation log properties
            case 'name':
                $this->name = $value;
                break;
            case 'function':
                $this->function = $value;
                break;
            case 'operation':
                $this->operation = $value;
                break;
            default:
                throw new \Exception('Invalid property: ' . $key . ' in Auditlog');
        }
    }

    /**
     * Get audit logs with required filters
     *
     * @param CustomersMailCloud $client The client instance
     * @param array $params Parameters for filtering
     * @return array Array of Auditlog instances
     * @throws CustomersMailCloudError If API returns errors
     * @throws \Exception For other errors (network, etc.)
     */
    public static function list(CustomersMailCloud $client, array $params = []): array
    {
        // Validate required parameters
        if (!isset($params['type'])) {
            throw new \Exception('type parameter is required');
        }

        // Validate type values
        if (!in_array($params['type'], ['login', 'operation'])) {
            throw new \Exception('type must be either "login" or "operation"');
        }

        // Validate date formats if provided
        if (isset($params['start_date']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $params['start_date'])) {
            throw new \Exception('start_date must be in yyyy-mm-dd format');
        }
        if (isset($params['end_date']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $params['end_date'])) {
            throw new \Exception('end_date must be in yyyy-mm-dd format');
        }

        // Validate date range if both dates are provided
        if (isset($params['start_date']) && isset($params['end_date'])) {
            $startDate = new \DateTime($params['start_date']);
            $endDate = new \DateTime($params['end_date']);
            if ($startDate > $endDate) {
                throw new \Exception('start_date must be earlier than or equal to end_date');
            }
            
            $daysDiff = $startDate->diff($endDate)->days;
            if ($daysDiff > 31) {
                throw new \Exception('search duration must be 31 days or less');
            }
        }

        // Build endpoint URL
        $url = sprintf('https://api.smtps.jp/transaction/v2/auditlogs/list.json');

        // Prepare data
        $data = [
            'api_user' => $client->getApiUser(),
            'api_key' => $client->getApiKey(),
            'type' => $params['type'],
        ];

        // Add optional parameters
        $optionalParams = ['account', 'start_date', 'end_date', 'p', 'r'];
        foreach ($optionalParams as $param) {
            if (isset($params[$param])) {
                $data[$param] = $params[$param];
            }
        }

        $httpClient = new GuzzleClient();

        try {
            $response = $httpClient->post($url, [
                'json' => $data,
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ]);

            $body = $response->getBody()->getContents();
            $responseData = json_decode($body, true);

            // Check for API errors
            if (isset($responseData['errors']) && !empty($responseData['errors'])) {
                throw new CustomersMailCloudError($responseData['errors'], $responseData);
            }

            // Convert response to Auditlog instances
            $auditlogs = [];
            
            // Handle login logs
            if (isset($responseData['loginlogs']) && is_array($responseData['loginlogs'])) {
                foreach ($responseData['loginlogs'] as $logData) {
                    $auditlogs[] = new self($logData);
                }
            }
            
            // Handle operation logs
            if (isset($responseData['operationlogs']) && is_array($responseData['operationlogs'])) {
                foreach ($responseData['operationlogs'] as $logData) {
                    $auditlogs[] = new self($logData);
                }
            }
            
            return $auditlogs;

        } catch (ClientException $e) {
            // Handle 4xx client errors
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
            throw new \Exception('Failed to get audit logs: ' . $e->getMessage());
        }
    }

    /**
     * Download audit logs as CSV file
     *
     * @param CustomersMailCloud $client The client instance
     * @param array $params Parameters for filtering
     * @return string Raw CSV file content as ZIP
     * @throws CustomersMailCloudError If API returns errors
     * @throws \Exception For other errors (network, etc.)
     */
    public static function download(CustomersMailCloud $client, array $params = []): string
    {
        // Validate required parameters
        if (!isset($params['type'])) {
            throw new \Exception('type parameter is required');
        }

        // Validate type values
        if (!in_array($params['type'], ['login', 'operation'])) {
            throw new \Exception('type must be either "login" or "operation"');
        }

        // Validate date formats if provided
        if (isset($params['start_date']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $params['start_date'])) {
            throw new \Exception('start_date must be in yyyy-mm-dd format');
        }
        if (isset($params['end_date']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $params['end_date'])) {
            throw new \Exception('end_date must be in yyyy-mm-dd format');
        }

        // Validate date range if both dates are provided
        if (isset($params['start_date']) && isset($params['end_date'])) {
            $startDate = new \DateTime($params['start_date']);
            $endDate = new \DateTime($params['end_date']);
            if ($startDate > $endDate) {
                throw new \Exception('start_date must be earlier than or equal to end_date');
            }
            
            $daysDiff = $startDate->diff($endDate)->days;
            if ($daysDiff > 31) {
                throw new \Exception('search duration must be 31 days or less');
            }
        }

        // Build endpoint URL
        $url = sprintf('https://api.smtps.jp/transaction/v2/auditlogs/download.json');

        // Prepare data
        $data = [
            'api_user' => $client->getApiUser(),
            'api_key' => $client->getApiKey(),
            'type' => $params['type'],
        ];

        // Add optional parameters
        $optionalParams = ['account', 'start_date', 'end_date'];
        foreach ($optionalParams as $param) {
            if (isset($params[$param])) {
                $data[$param] = $params[$param];
            }
        }

        $httpClient = new GuzzleClient();

        try {
            $response = $httpClient->post($url, [
                'json' => $data,
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ]);

            // For download endpoint, return raw binary content (ZIP file)
            return $response->getBody()->getContents();

        } catch (ClientException $e) {
            // Handle 4xx client errors
            $response = $e->getResponse();
            if ($response) {
                $body = $response->getBody()->getContents();
                $responseData = json_decode($body, true);

                if ($responseData && isset($responseData['errors']) && !empty($responseData['errors'])) {
                    throw new CustomersMailCloudError($responseData['errors'], $responseData);
                }
            }
            throw new \Exception('Client error: ' . $e->getMessage());

        } catch (ServerException $e) {
            // Handle 5xx server errors
            throw new \Exception('Server error: ' . $e->getMessage());

        } catch (GuzzleException $e) {
            // Handle other Guzzle exceptions (network errors, etc.)
            throw new \Exception('Failed to download audit logs: ' . $e->getMessage());
        }
    }
}