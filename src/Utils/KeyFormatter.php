<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Utils;

use Marzsv\DteSigner\Exceptions\DteSignerException;

/**
 * Utility class for formatting and processing cryptographic keys
 */
class KeyFormatter
{
    /**
     * Convert a private key to PEM format
     *
     * Handles both:
     * - Already PEM-formatted keys (returned as-is)
     * - Base64-encoded DER keys (converted to PEM)
     *
     * @param string $privateKey The private key (PEM or base64 DER format)
     * @return string The key in PEM format
     * @throws DteSignerException If the key format is invalid
     */
    public static function toPem(string $privateKey): string
    {
        // If it already looks like a PEM key, use it directly
        if (self::isPemFormat($privateKey)) {
            return $privateKey;
        }

        // If it's base64 encoded (from MH certificates), decode it
        $decodedKey = base64_decode($privateKey, true);
        if ($decodedKey === false) {
            throw new DteSignerException('Invalid base64 private key format', 'COD_815');
        }

        // Convert DER to PEM format
        return "-----BEGIN PRIVATE KEY-----\n" .
               chunk_split(base64_encode($decodedKey), 64, "\n") .
               "-----END PRIVATE KEY-----";
    }

    /**
     * Convert a private key to PEM format and decrypt it if password is provided
     *
     * @param string $privateKey The private key (PEM or base64 DER format)
     * @param string|null $password Optional password to decrypt the key
     * @return string The decrypted key in PEM format
     * @throws DteSignerException If the key format is invalid or decryption fails
     */
    public static function toPemDecrypted(string $privateKey, ?string $password = null): string
    {
        $pemKey = self::toPem($privateKey);

        // If no password provided, return the PEM key directly
        if ($password === null) {
            return $pemKey;
        }

        // Try to decrypt the key with password
        $resource = openssl_pkey_get_private($pemKey, $password);
        if ($resource === false) {
            // If decryption with password fails, try without password
            $resource = openssl_pkey_get_private($pemKey);
            if ($resource === false) {
                $error = openssl_error_string();
                throw new DteSignerException(
                    'Cannot decrypt private key with provided password' . ($error !== false ? ': ' . $error : ''),
                    'COD_814'
                );
            }
        }

        // Export the decrypted key
        $decryptedPem = '';
        if (!openssl_pkey_export($resource, $decryptedPem)) {
            $error = openssl_error_string();
            throw new DteSignerException(
                'Failed to export decrypted private key' . ($error !== false ? ': ' . $error : ''),
                'COD_815'
            );
        }

        if (!is_string($decryptedPem)) {
            throw new DteSignerException('Exported private key is not a valid string', 'COD_815');
        }

        return $decryptedPem;
    }

    /**
     * Check if a key string is in PEM format
     */
    public static function isPemFormat(string $key): bool
    {
        return str_contains($key, '-----BEGIN') && str_contains($key, '-----END');
    }
}
