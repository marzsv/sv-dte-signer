<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Validators;

use Marzsv\DteSigner\Exceptions\ValidationException;

/**
 * Validates DTE signing requests according to PRD specifications
 */
class RequestValidator
{
    private const MIN_PASSWORD_LENGTH = 8;
    private const MAX_PASSWORD_LENGTH = 100;

    /** @var array<string> */
    private array $requiredFields = ['nit', 'privateKeyPassword', 'dteJson'];

    /**
     * Validate a signing request
     *
     * @param array<string, mixed> $request
     * @throws ValidationException
     */
    public function validate(array $request): void
    {
        $errors = [];

        $errors = array_merge($errors, $this->validateRequiredFields($request));
        $errors = array_merge($errors, NitValidator::validate($request['nit'] ?? ''));
        $errors = array_merge($errors, $this->validatePassword($request['privateKeyPassword'] ?? ''));
        $errors = array_merge($errors, $this->validateDteJson($request['dteJson'] ?? null));

        if (!empty($errors)) {
            throw new ValidationException('Request validation failed', $errors);
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array<string, mixed> $request
     * @return array<string>
     */
    private function validateRequiredFields(array $request): array
    {
        $errors = [];

        foreach ($this->requiredFields as $field) {
            if (!array_key_exists($field, $request) || empty($request[$field])) {
                $errors[] = "Required field '{$field}' is missing or empty";
            }
        }

        return $errors;
    }

    /**
     * Validate password length (8-100 characters)
     * 
     * @return array<string>
     */
    private function validatePassword(string $password): array
    {
        $errors = [];
        $length = strlen($password);

        if ($length < self::MIN_PASSWORD_LENGTH) {
            $errors[] = "Password must be at least " . self::MIN_PASSWORD_LENGTH . " characters long";
        }

        if ($length > self::MAX_PASSWORD_LENGTH) {
            $errors[] = "Password must not exceed " . self::MAX_PASSWORD_LENGTH . " characters";
        }

        return $errors;
    }

    /**
     * Validate DTE JSON is not null
     * 
     * @return array<string>
     */
    private function validateDteJson(mixed $dteJson): array
    {
        $errors = [];

        if ($dteJson === null) {
            $errors[] = 'DTE JSON cannot be null';
        }

        return $errors;
    }
}