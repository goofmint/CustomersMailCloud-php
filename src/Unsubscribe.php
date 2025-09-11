<?php

declare(strict_types=1);

namespace CustomersMailCloud;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

/**
 * Unsubscribe class for handling unsubscribe operations
 */
class Unsubscribe
{
    public string $created;
    public string $email;
    public string $filtername;

    /**
     * Unsubscribe constructor.
     *
     * @param array $data Unsubscribe data from API response
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
            case 'email':
                $this->email = $value;
                break;
            case 'filtername':
                $this->filtername = $value;
                break;
            default:
                throw new \Exception('Invalid property: ' . $key . ' in Unsubscribe');
        }
    }

    /**
     * Download unsubscribe list as CSV file
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
        if (!isset($params['server_composition'])) {
            throw new \Exception('server_composition parameter is required');
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
        }

        // Build endpoint URL
        $url = sprintf('https://api.smtps.jp/transaction/v2/unsubscribes/download.json');

        // Prepare data
        $data = [
            'api_user' => $client->getApiUser(),
            'api_key' => $client->getApiKey(),
            'server_composition' => $params['server_composition'],
        ];

        // Add optional parameters
        $optionalParams = ['email', 'start_date', 'end_date', 'filter_name'];
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
            throw new \Exception('Failed to download unsubscribes: ' . $e->getMessage());
        }
    }

    /**
     * Cancel unsubscribe status (resubscribe)
     *
     * @param CustomersMailCloud $client The client instance
     * @param array $params Parameters for canceling unsubscribe
     * @return array Response from API
     * @throws CustomersMailCloudError If API returns errors
     * @throws \Exception For other errors (network, etc.)
     */
    public static function cancel(CustomersMailCloud $client, array $params = []): array
    {
        // Validate required parameters
        if (!isset($params['server_composition'])) {
            throw new \Exception('server_composition parameter is required');
        }
        if (!isset($params['email'])) {
            throw new \Exception('email parameter is required');
        }

        // Build endpoint URL
        $url = sprintf('https://api.smtps.jp/transaction/v2/unsubscribes/cancel.json');

        // Prepare data
        $data = [
            'api_user' => $client->getApiUser(),
            'api_key' => $client->getApiKey(),
            'server_composition' => $params['server_composition'],
            'email' => $params['email'],
        ];

        // Add optional parameters
        $optionalParams = ['filter_name'];
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

            return $responseData;

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
            throw new \Exception('Failed to cancel unsubscribe: ' . $e->getMessage());
        }
    }
}