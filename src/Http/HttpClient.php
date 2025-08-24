<?php

declare(strict_types=1);

namespace CustomersMailCloud\Http;

use CustomersMailCloud\Exception\ApiException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

class HttpClient
{
    private GuzzleClient $client;
    private string $apiUser;
    private string $apiKey;

    public function __construct(array $config, string $apiUser, string $apiKey)
    {
        $this->apiUser = $apiUser;
        $this->apiKey = $apiKey;

        $this->client = new GuzzleClient([
            'base_uri' => $config['base_uri'],
            'timeout' => $config['timeout'] ?? 30,
            'verify' => $config['verify_ssl'] ?? true,
            'headers' => [
                'User-Agent' => 'CustomersMailCloud-PHP-SDK/1.0.0',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Make a GET request
     *
     * @param string $endpoint
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, ['query' => $params]);
    }

    /**
     * Make a POST request
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws ApiException
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, ['json' => $data]);
    }

    /**
     * Make a PUT request
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws ApiException
     */
    public function put(string $endpoint, array $data = []): array
    {
        return $this->request('PUT', $endpoint, ['json' => $data]);
    }

    /**
     * Make a DELETE request
     *
     * @param string $endpoint
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function delete(string $endpoint, array $params = []): array
    {
        return $this->request('DELETE', $endpoint, ['query' => $params]);
    }

    /**
     * Make an HTTP request
     *
     * @param string $method
     * @param string $endpoint
     * @param array $options
     * @return array
     * @throws ApiException
     */
    private function request(string $method, string $endpoint, array $options = []): array
    {
        // Add authentication
        $options['auth'] = [$this->apiUser, $this->apiKey];

        try {
            $response = $this->client->request($method, $endpoint, $options);
            return $this->parseResponse($response);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $body = json_decode((string) $response->getBody(), true);
                $message = $body['message'] ?? $e->getMessage();
                $code = $response->getStatusCode();
            } else {
                $message = $e->getMessage();
                $code = 0;
            }

            throw new ApiException($message, $code, $e);
        } catch (GuzzleException $e) {
            throw new ApiException('HTTP request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Parse the API response
     *
     * @param ResponseInterface $response
     * @return array
     * @throws ApiException
     */
    private function parseResponse(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();

        if (empty($body)) {
            return [];
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException('Failed to parse response: ' . json_last_error_msg());
        }

        return $data;
    }
}