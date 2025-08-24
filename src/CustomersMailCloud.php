<?php

declare(strict_types=1);

namespace CustomersMailCloud;

/**
 * CustomersMailCloud main class
 */
class CustomersMailCloud
{
    private string $apiUser;
    private string $apiKey;
    public string $sub_domain;

    /**
     * CustomersMailCloud constructor.
     *
     * @param string $apiUser API user
     * @param string $apiKey API key
     */
    public function __construct(string $apiUser, string $apiKey)
    {
        $this->apiUser = $apiUser;
        $this->apiKey = $apiKey;
        $this->sub_domain = 'sandbox';
    }

    /**
     * Get API user
     *
     * @return string
     */
    public function getApiUser(): string
    {
        return $this->apiUser;
    }

    /**
     * Get API key
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Create a new TransactionEmail instance
     *
     * @return TransactionEmail
     */
    public function transaction_email(): TransactionEmail
    {
        return new TransactionEmail($this);
    }
}