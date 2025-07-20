<?php

declare(strict_types=1);

namespace DteSigner\Exceptions;

/**
 * Exception thrown when input validation fails
 */
class ValidationException extends DteSignerException
{
    private const VALIDATION_ERROR_CODE = 'COD_803';

    public function __construct(
        string $message = 'Validation error',
        array $errors = [],
        int $code = 0,
        ?DteSignerException $previous = null
    ) {
        parent::__construct($message, self::VALIDATION_ERROR_CODE, $errors, $code, $previous);
    }
}