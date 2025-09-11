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

    /**
     * Get deliveries with optional parameters
     *
     * @param array $params Optional parameters for filtering deliveries
     * @return array Array of Delivery instances
     */
    public function deliveries(array $params = []): array
    {
        return Delivery::list($this, $params);
    }

    /**
     * Get bounces with optional parameters
     *
     * @param array $params Optional parameters for filtering bounces
     * @return array Array of Bounce instances
     */
    public function bounces(array $params = []): array
    {
        return Bounce::list($this, $params);
    }

    /**
     * Get statistics with required parameters
     *
     * @param array $params Parameters for filtering statistics (year and month required)
     * @return array Array of Statistic instances
     */
    public function statistics(array $params = []): array
    {
        return Statistic::list($this, $params);
    }

    /**
     * Get audit logs with required parameters
     *
     * @param array $params Parameters for filtering audit logs (type required)
     * @return array Array of Auditlog instances
     */
    public function auditlogs(array $params = []): array
    {
        return Auditlog::list($this, $params);
    }

    /**
     * Download unsubscribe list as ZIP file
     *
     * @param array $params Parameters for filtering unsubscribes (server_composition required)
     * @return string Raw ZIP file content
     */
    public function unsubscribes_download(array $params = []): string
    {
        return Unsubscribe::download($this, $params);
    }

    /**
     * Cancel unsubscribe status (resubscribe)
     *
     * @param array $params Parameters for canceling unsubscribe (server_composition and email required)
     * @return array Response from API
     */
    public function unsubscribes_cancel(array $params = []): array
    {
        return Unsubscribe::cancel($this, $params);
    }
}