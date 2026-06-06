<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Utils\Formatters;

use Marzsv\DteSigner\Contracts\KeyFormatterInterface;
use Marzsv\DteSigner\Exceptions\DteSignerException;

/**
 * Formats PEM-encoded private keys
 */
class PemKeyFormatter implements KeyFormatterInterface
{
    public function supports(string $key): bool
    {
        return str_contains($key, '-----BEGIN') && str_contains($key, '-----END');
    }

    public function toPem(string $key): string
    {
        return $key;
    }

    public function toPemDecrypted(string $key, ?string $password = null): string
    {
        $privateKeyResource = openssl_pkey_get_private($key, $password ?? '');

        if ($privateKeyResource === false && $password !== null) {
            $privateKeyResource = openssl_pkey_get_private($key);
        }

        if ($privateKeyResource === false) {
            $error = openssl_error_string();
            throw new DteSignerException(
                'Cannot load private key' . ($error ? ': ' . $error : ''),
                'COD_814'
            );
        }

        $decryptedKey = '';
        if (!openssl_pkey_export($privateKeyResource, $decryptedKey)) {
            $error = openssl_error_string();
            throw new DteSignerException(
                'Cannot export private key' . ($error ? ': ' . $error : ''),
                'COD_815'
            );
        }

        return $decryptedKey;
    }
}
