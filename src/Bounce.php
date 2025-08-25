<?php

declare(strict_types=1);

namespace CustomersMailCloud;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

/**
 * Bounce class for handling bounce operations
 */
class Bounce
{
    public string $created;
    public string $status;
    public string $from;
    public string $to;
    public string $messageId;
    public string $returnPath;
    public string $subject;
    public string $apiData;
    public string $reason;

    /**
     * Bounce constructor.
     *
     * @param array $data Bounce data from API response
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
            case 'status':
                $this->status = $value;
                break;
            case 'from':
                $this->from = $value;
                break;
            case 'to':
                $this->to = $value;
                break;
            case 'messageid':
                $this->messageId = $value;
                break;
            case 'returnpath':
                $this->returnPath = $value;
                break;
            case 'subject':
                $this->subject = $value;
                break;
            case 'apidata':
                $this->apiData = $value;
                break;
            case 'reason':
                $this->reason = $value;
                break;
            default:
                throw new \Exception('Invalid property: ' . $key . ' in Bounce');
        }
    }

    /**
     * Get bounce list with optional filters
     *
     * @param CustomersMailCloud $client The client instance
     * @param array $params Optional parameters for filtering
     * @return array Array of Bounce instances
     * @throws CustomersMailCloudError If API returns errors
     * @throws \Exception For other errors (network, etc.)
     */
    public static function list(CustomersMailCloud $client, array $params = []): array
    {
        // Validate required parameters
        if (!isset($params['server_composition'])) {
            throw new \Exception('server_composition parameter is required');
        }

        // Build endpoint URL
        $url = sprintf('https://api.smtps.jp/transaction/v2/bounces/list.json');

        // Prepare data
        $data = [
            'api_user' => $client->getApiUser(),
            'api_key' => $client->getApiKey(),
            'server_composition' => $params['server_composition'],
        ];

        // Add optional parameters
        $optionalParams = ['from', 'to', 'api_data', 'status', 'start_date', 'end_date', 'date', 'hour', 'minute', 'p', 'r', 'search_option'];
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

            // Convert response to Bounce instances
            $bounces = [];
            if (isset($responseData['bounces']) && is_array($responseData['bounces'])) {
                foreach ($responseData['bounces'] as $bounceData) {
                    $bounces[] = new self($bounceData);
                }
            }
            
            return $bounces;

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
            throw new \Exception('Failed to get bounces: ' . $e->getMessage());
        }
    }
}