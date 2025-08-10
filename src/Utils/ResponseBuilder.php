<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Utils;

use Marzsv\DteSigner\Exceptions\DteSignerException;

/**
 * Builds standardized responses for DTE signing operations
 */
class ResponseBuilder
{
    private const SUCCESS_MESSAGE = 'DTE signed successfully';

    /**
     * Build a success response with the signed JWS
     * 
     * @return array<string, mixed>
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
     * Build a success response for verification operations
     * 
     * @param array<string, mixed> $payload The verified or extracted payload
     * @return array<string, mixed>
     */
    public static function verificationSuccess(array $payload, string $message): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $payload
        ];
    }

    /**
     * Build an error response from an exception
     * 
     * @return array<string, mixed>
     */
    public static function error(DteSignerException $exception): array
    {
        return $exception->toArray();
    }

    /**
     * Build a generic error response
     * 
     * @param array<string> $errors
     * @return array<string, mixed>
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