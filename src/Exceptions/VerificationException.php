<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Exceptions;

/**
 * Exception for DTE verification and payload extraction errors
 */
class VerificationException extends DteSignerException
{
    private const DEFAULT_ERROR_CODE = 'COD_820';

    /**
     * Create a new VerificationException
     * 
     * @param string $message Error message
     * @param array<string> $errors Detailed error list
     * @param string $code Error code
     */
    public function __construct(
        string $message,
        array $errors = [],
        string $code = self::DEFAULT_ERROR_CODE
    ) {
        parent::__construct($message, $code, $errors);
    }
}