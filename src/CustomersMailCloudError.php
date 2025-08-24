<?php

declare(strict_types=1);

namespace CustomersMailCloud;

/**
 * CustomersMailCloudError class for handling API errors
 */
class CustomersMailCloudError extends \Exception
{
    public array $errors;
    public array $rawResponse;

    /**
     * CustomersMailCloudError constructor.
     *
     * @param array $errors Array of error objects from API
     * @param array $rawResponse The full raw response from API
     * @param string $message Optional custom message
     */
    public function __construct(array $errors, array $rawResponse, string $message = '')
    {
        $this->errors = $errors;
        $this->rawResponse = $rawResponse;

        if (empty($message)) {
            $message = $this->buildErrorMessage();
        }

        parent::__construct($message);
    }

    /**
     * Build error message from errors array
     *
     * @return string
     */
    private function buildErrorMessage(): string
    {
        if (empty($this->errors)) {
            return 'Unknown API error occurred';
        }

        $messages = [];
        foreach ($this->errors as $error) {
            $errorMessage = $error['message'] ?? 'Unknown error';
            $errorCode = $error['code'] ?? '';
            $field = $error['field'] ?? '';

            $fullMessage = $errorMessage;
            if (!empty($errorCode)) {
                $fullMessage = "[{$errorCode}] " . $fullMessage;
            }
            if (!empty($field)) {
                $fullMessage .= " (field: {$field})";
            }

            $messages[] = $fullMessage;
        }

        return implode('; ', $messages);
    }

    /**
     * Get the first error code
     *
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->errors[0]['code'] ?? '';
    }

    /**
     * Get the first error field
     *
     * @return string
     */
    public function getErrorField(): string
    {
        return $this->errors[0]['field'] ?? '';
    }

    /**
     * Get all error codes
     *
     * @return array
     */
    public function getAllErrorCodes(): array
    {
        $codes = [];
        foreach ($this->errors as $error) {
            if (!empty($error['code'])) {
                $codes[] = $error['code'];
            }
        }
        return $codes;
    }

    /**
     * Check if error has specific code
     *
     * @param string $code
     * @return bool
     */
    public function hasErrorCode(string $code): bool
    {
        return in_array($code, $this->getAllErrorCodes());
    }

    /**
     * Get formatted error information
     *
     * @return array
     */
    public function getErrorInfo(): array
    {
        return [
            'message' => $this->getMessage(),
            'errors' => $this->errors,
            'error_codes' => $this->getAllErrorCodes(),
            'raw_response' => $this->rawResponse
        ];
    }
}