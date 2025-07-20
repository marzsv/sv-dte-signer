<?php

declare(strict_types=1);

namespace DteSigner\Utils;

use DteSigner\Exceptions\DteSignerException;

/**
 * Builds standardized responses for DTE signing operations
 */
class ResponseBuilder
{
    private const SUCCESS_MESSAGE = 'DTE signed successfully';

    /**
     * Build a success response with the signed JWS
     */
    public static function success(string $signedJws, string $message = self::SUCCESS_MESSAGE): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $signedJws
        ];
    }

    /**
     * Build an error response from an exception
     */
    public static function error(DteSignerException $exception): array
    {
        return $exception->toArray();
    }

    /**
     * Build a generic error response
     */
    public static function genericError(
        string $message,
        string $errorCode = 'COD_500',
        array $errors = []
    ): array {
        return [
            'success' => false,
            'message' => $message,
            'errorCode' => $errorCode,
            'errors' => $errors
        ];
    }
}