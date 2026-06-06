<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Utils\Formatters;

use Marzsv\DteSigner\Contracts\KeyFormatterInterface;
use Marzsv\DteSigner\Exceptions\DteSignerException;

/**
 * Formats base64-encoded DER private keys
 *
 * Handles keys in base64-DER format (no PEM headers) and converts them
 * to PEM format for use with OpenSSL functions.
 */
class DerKeyFormatter implements KeyFormatterInterface
{
    public function supports(string $key): bool
    {
        if (str_contains($key, '-----BEGIN') || str_contains($key, '-----END')) {
            return false;
        }

        $decoded = base64_decode($key, true);
        return $decoded !== false;
    }

    public function toPem(string $key): string
    {
        $decoded = base64_decode($key, true);
        if ($decoded === false) {
            throw new DteSignerException(
                'Invalid base64 private key format',
                'COD_815'
            );
        }

        $base64Encoded = base64_encode($decoded);
        $chunks = str_split($base64Encoded, 64);
        $pemBody = implode("\n", $chunks);

        return "-----BEGIN PRIVATE KEY-----\n{$pemBody}\n-----END PRIVATE KEY-----\n";
    }

    public function toPemDecrypted(string $key, ?string $password = null): string
    {
        $pem = $this->toPem($key);
        $pemFormatter = new PemKeyFormatter();
        return $pemFormatter->toPemDecrypted($pem, $password);
    }
}
