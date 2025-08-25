<?php

declare(strict_types=1);

namespace CustomersMailCloud;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

/**
 * Delivery class for handling delivery status operations
 */
class Delivery
{
    public string $created;
    public string $returnPath;
    public string $from;
    public string $to;
    public string $messageId;
    public string $reason;
    public string $senderIp;
    public string $sourceIp;
    public string $status;
    public string $subject;
    public string $apiData;

    /**
     * Delivery constructor.
     *
     * @param array $data Delivery data from API response
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
            case 'returnpath':
                $this->returnPath = $value;
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
            case 'reason':
                $this->reason = $value;
                break;
            case 'senderip':
                $this->senderIp = $value;
                break;
            case 'sourceip':
                $this->sourceIp = $value;
                break;
            case 'status':
                $this->status = $value;
                break;
            case 'subject':
                $this->subject = $value;
                break;
            case 'apidata':
                $this->apiData = $value;
                break;
            default:
                throw new \Exception('Invalid property: ' . $key . ' in Delivery');
        }
    }

    /**
     * Get delivery list with optional filters
     *
     * @param CustomersMailCloud $client The client instance
     * @param array $params Optional parameters for filtering
     * @return array Array of Delivery instances
     * @throws CustomersMailCloudError If API returns errors
     * @throws \Exception For other errors (network, etc.)
     */
    public static function list(CustomersMailCloud $client, array $params = []): array
    {
        // Validate required parameters
        if (!isset($params['server_composition'])) {
            throw new \Exception('server_composition parameter is required');
        }
        if (!isset($params['date'])) {
            throw new \Exception('date parameter is required');
        }

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $params['date'])) {
            throw new \Exception('date must be in yyyy-mm-dd format');
        }

        // Build endpoint URL
        $url = sprintf('https://api.smtps.jp/transaction/v2/deliveries/list.json');

        // Prepare data
        $data = [
            'api_user' => $client->getApiUser(),
            'api_key' => $client->getApiKey(),
            'server_composition' => $params['server_composition'],
            'date' => $params['date'],
        ];

        // Add optional parameters
        $optionalParams = ['from', 'to', 'api_data', 'status', 'hour', 'minute', 'p', 'r', 'search_option'];
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

            // Convert response to Delivery instances
            $deliveries = [];
            if (isset($responseData['deliveries']) && is_array($responseData['deliveries'])) {
                foreach ($responseData['deliveries'] as $deliveryData) {
                    $deliveries[] = new self($deliveryData);
                }
            }
            
            return $deliveries;

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
            throw new \Exception('Failed to get deliveries: ' . $e->getMessage());
        }
    }

    /**
     * Get delivery by message ID (convenience method)
     *
     * @param string $messageId Message ID to search for
     * @param string $serverComposition Server composition
     * @param string $date Date in yyyy-mm-dd format
     * @param array $additionalParams Additional search parameters
     * @return array Array of matching delivery records
     */
    public function getByMessageId(CustomersMailCloud $client, string $messageId, string $serverComposition, string $date, array $additionalParams = []): array
    {
        $params = array_merge($additionalParams, [
            'server_composition' => $serverComposition,
            'date' => $date,
            'api_data' => $messageId,
            'search_option' => ['api_data' => 'full']
        ]);

        return self::list($client, $params);
    }

    /**
     * Get deliveries by email address (convenience method)
     *
     * @param string $email Email address to search for
     * @param string $serverComposition Server composition
     * @param string $date Date in yyyy-mm-dd format
     * @param string $type Search type ('from' or 'to')
     * @param array $additionalParams Additional search parameters
     * @return array Array of matching delivery records
     */
    public function getByEmail(CustomersMailCloud $client, string $email, string $serverComposition, string $date, string $type = 'to', array $additionalParams = []): array
    {
        if (!in_array($type, ['from', 'to'])) {
            throw new \Exception('type must be either "from" or "to"');
        }

        $params = array_merge($additionalParams, [
            'server_composition' => $serverComposition,
            'date' => $date,
            $type => $email,
            'search_option' => [$type => 'full']
        ]);

        return self::list($client, $params);
    }

    /**
     * Get deliveries by status (convenience method)
     *
     * @param string $status Delivery status to search for
     * @param string $serverComposition Server composition
     * @param string $date Date in yyyy-mm-dd format
     * @param array $additionalParams Additional search parameters
     * @return array Array of matching delivery records
     */
    public function getByStatus(CustomersMailCloud $client, string $status, string $serverComposition, string $date, array $additionalParams = []): array
    {
        $params = array_merge($additionalParams, [
            'server_composition' => $serverComposition,
            'date' => $date,
            'status' => $status
        ]);

        return self::list($client, $params);
    }
}