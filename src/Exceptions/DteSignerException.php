<?php

declare(strict_types=1);

namespace DteSigner\Exceptions;

use Exception;

/**
 * Base exception class for DTE Signer operations
 */
class DteSignerException extends Exception
{
    private const DEFAULT_ERROR_CODE = 'COD_001';

    private string $errorCode;
    /** @var array<string> */
    private array $errors;

    /**
     * @param array<string> $errors
     */
    public function __construct(
        string $message = '',
        string $errorCode = self::DEFAULT_ERROR_CODE,
        array $errors = [],
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->errors = $errors;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => false,
            'message' => $this->getMessage(),
            'errorCode' => $this->errorCode,
            'errors' => $this->errors
        ];
    }
}