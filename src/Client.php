<?php

declare(strict_types=1);

namespace CustomersMailCloud;

use CustomersMailCloud\Exception\ApiException;
use CustomersMailCloud\Exception\ConfigurationException;
use CustomersMailCloud\Http\HttpClient;

class Client
{
    private HttpClient $httpClient;
    private string $apiKey;
    private string $apiUser;
    private array $config;

    /**
     * Client constructor.
     *
     * @param string $apiUser API user
     * @param string $apiKey API key
     * @param array $config Additional configuration options
     */
    public function __construct(string $apiUser, string $apiKey, array $config = [])
    {
        $this->apiUser = $apiUser;
        $this->apiKey = $apiKey;
        $this->config = array_merge([
            'timeout' => 30,
            'verify_ssl' => true,
        ], $config);

        $this->httpClient = new HttpClient($this->config, $this->apiUser, $this->apiKey);
    }

    /**
     * Get the HTTP client instance
     *
     * @return HttpClient
     */
    public function getHttpClient(): HttpClient
    {
        return $this->httpClient;
    }
}