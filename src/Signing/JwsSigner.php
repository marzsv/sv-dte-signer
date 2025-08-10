<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Signing;

use Marzsv\DteSigner\Exceptions\DteSignerException;
use Firebase\JWT\JWT;

/**
 * Creates JWS signatures using RS512 algorithm
 */
class JwsSigner
{
    private const ALGORITHM = 'RS512';

    /**
     * Sign DTE JSON data and return JWS compact serialization
     * 
     * @param array<string, mixed> $dteJson
     * @throws DteSignerException
     */
    public function sign(array $dteJson, string $privateKey, ?string $password = null): string
    {
        try {
            if (empty($privateKey)) {
                throw new DteSignerException(
                    'Private key cannot be empty',
                    'COD_815'
                );
            }
            
            if (empty($dteJson)) {
                throw new DteSignerException(
                    'DTE JSON cannot be empty',
                    'COD_816'
                );
            }
            
            // Process the private key (handle both PEM and base64 formats)
            $processedKey = $this->processPrivateKey($privateKey, $password);
            
            $header = ['alg' => self::ALGORITHM, 'typ' => 'JWT'];

            return JWT::encode($dteJson, $processedKey, self::ALGORITHM, null, $header);

        } catch (\Exception $e) {
            throw new DteSignerException(
                'Failed to sign DTE: ' . $e->getMessage(),
                'COD_815'
            );
        }
    }

    /**
     * Process private key to ensure it's in the correct format for OpenSSL
     */
    private function processPrivateKey(string $privateKey, ?string $password = null): string
    {
        // If it already looks like a PEM key, use it directly
        if (str_contains($privateKey, '-----BEGIN') && str_contains($privateKey, '-----END')) {
            return $privateKey;
        }

        // If it's base64 encoded (from MH certificates), decode it
        $decodedKey = base64_decode($privateKey, true);
        if ($decodedKey === false) {
            throw new DteSignerException('Invalid base64 private key format', 'COD_815');
        }

        // Convert DER to PEM format
        $pemKey = "-----BEGIN PRIVATE KEY-----\n" . 
                  chunk_split(base64_encode($decodedKey), 64, "\n") . 
                  "-----END PRIVATE KEY-----";

        // If we have a password, try to decrypt the key
        if ($password !== null) {
            $resource = openssl_pkey_get_private($pemKey, $password);
            if ($resource === false) {
                // If decryption with password fails, try without password
                $resource = openssl_pkey_get_private($pemKey);
                if ($resource === false) {
                    throw new DteSignerException(
                        'Cannot decrypt private key with provided password: ' . openssl_error_string(),
                        'COD_814'
                    );
                }
            }
            
            // Export the decrypted key
            if (!openssl_pkey_export($resource, $decryptedPem)) {
                throw new DteSignerException(
                    'Failed to export decrypted private key: ' . openssl_error_string(),
                    'COD_815'
                );
            }
            
            return $decryptedPem;
        }

        return $pemKey;
    }
}