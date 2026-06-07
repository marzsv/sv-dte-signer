<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Validators;

/**
 * Centralized NIT (Número de Identificación Tributaria) validation for El Salvador
 *
 * NIT format: 14 numeric digits
 */
class NitValidator
{
    private const NIT_LENGTH = 14;
    private const NIT_PATTERN = '/^\d{14}$/';

    /**
     * Check if a NIT is valid
     *
     * @param string $nit The NIT to validate
     * @return bool True if valid, false otherwise
     */
    public static function isValid(string $nit): bool
    {
        return preg_match(self::NIT_PATTERN, $nit) === 1;
    }

    /**
     * Validate a NIT and return validation errors
     *
     * @param string $nit The NIT to validate
     * @return array<string> Array of error messages, empty if valid
     */
    public static function validate(string $nit): array
    {
        $errors = [];

        if (strlen($nit) !== self::NIT_LENGTH) {
            $errors[] = 'NIT must be exactly 14 characters long';
        }

        if (preg_match(self::NIT_PATTERN, $nit) !== 1) {
            $errors[] = 'NIT must contain only digits';
        }

        return $errors;
    }

    /**
     * Get the expected NIT length
     */
    public static function getExpectedLength(): int
    {
        return self::NIT_LENGTH;
    }
}
