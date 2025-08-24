<?php

declare(strict_types=1);

namespace CustomersMailCloud\Exception;

use Exception;

class ApiException extends Exception
{
    private ?array $errors = null;

    /**
     * Get API errors if available
     *
     * @return array|null
     */
    public function getErrors(): ?array
    {
        return $this->errors;
    }

    /**
     * Set API errors
     *
     * @param array $errors
     * @return void
     */
    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }
}