<?php

declare(strict_types=1);

namespace CustomersMailCloud;

/**
 * EmailAddress class for handling email addresses
 */
class EmailAddress
{
    public string $name;
    public string $address;
    public ?array $substitutions;

    /**
     * EmailAddress constructor.
     *
     * @param string $address Email address
     * @param string $name Display name
     * @param array|null $substitutions Substitution variables (optional)
     */
    public function __construct(string $address, string $name = '', ?array $substitutions = null)
    {
        $this->address = $address;
        $this->name = $name;
        $this->substitutions = $substitutions;
    }

    /**
     * Convert EmailAddress to JSON format for API
     *
     * @return string JSON representation of the email address
     */
    public function to_json(): string
    {
        $data = [
            'address' => $this->address,
            'name' => $this->name
        ];

        if ($this->substitutions !== null && is_array($this->substitutions)) {
            $data = array_merge($data, $this->substitutions);
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}