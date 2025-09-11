<?php

declare(strict_types=1);

namespace CustomersMailCloud;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

/**
 * Statistic class for handling statistics operations
 */
class Statistic
{
    public string $date;
    public int $queued;
    public int $succeeded;
    public int $failed;
    public int $blocked;
    public int $valid;

    /**
     * Statistic constructor.
     *
     * @param array $data Statistic data from API response
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
            case 'date':
                $this->date = $value;
                break;
            case 'queued':
                $this->queued = (int)$value;
                break;
            case 'succeeded':
                $this->succeeded = (int)$value;
                break;
            case 'failed':
                $this->failed = (int)$value;
                break;
            case 'blocked':
                $this->blocked = (int)$value;
                break;
            case 'valid':
                $this->valid = (int)$value;
                break;
            default:
                throw new \Exception('Invalid property: ' . $key . ' in Statistic');
        }
    }

    /**
     * Get statistics list with required filters
     *
     * @param CustomersMailCloud $client The client instance
     * @param array $params Parameters for filtering
     * @return array Array of Statistic instances
     * @throws CustomersMailCloudError If API returns errors
     * @throws \Exception For other errors (network, etc.)
     */
    public static function list(CustomersMailCloud $client, array $params = []): array
    {
        // Validate required parameters
        if (!isset($params['year'])) {
            throw new \Exception('year parameter is required');
        }
        if (!isset($params['month'])) {
            throw new \Exception('month parameter is required');
        }

        // Validate year and month format
        if (!is_numeric($params['year']) || strlen((string)$params['year']) !== 4) {
            throw new \Exception('year must be a 4-digit number');
        }
        if (!is_numeric($params['month']) || $params['month'] < 1 || $params['month'] > 12) {
            throw new \Exception('month must be a number between 1 and 12');
        }

        // Build endpoint URL
        $url = sprintf('https://api.smtps.jp/transaction/v2/statistics/list.json');

        // Prepare data
        $data = [
            'api_user' => $client->getApiUser(),
            'api_key' => $client->getApiKey(),
            'year' => $params['year'],
            'month' => $params['month'],
        ];

        // Add optional parameters
        $optionalParams = ['server_composition', 'total'];
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

            // Convert response to Statistic instances
            $statistics = [];
            if (isset($responseData['statistics']) && is_array($responseData['statistics'])) {
                foreach ($responseData['statistics'] as $statisticData) {
                    $statistics[] = new self($statisticData);
                }
            }
            
            return $statistics;

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
            throw new \Exception('Failed to get statistics: ' . $e->getMessage());
        }
    }
}