<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Contracts;

/**
 * Contract for formatting and converting private keys between formats
 *
 * Implementations handle different key formats (PEM, DER, etc.)
 */
interface KeyFormatterInterface
{
    /**
     * Check if this formatter supports the given key
     *
     * @param string $key The key to check
     * @return bool True if this formatter can handle the key
     */
    public function supports(string $key): bool;

    /**
     * Convert key to PEM format
     *
     * @param string $key The private key in any supported format
     * @return string The key in PEM format
     * @throws \Marzsv\DteSigner\Exceptions\DteSignerException
     */
    public function toPem(string $key): string;

    /**
     * Convert key to PEM format and optionally decrypt
     *
     * @param string $key The private key in any supported format
     * @param string|null $password Optional password for encrypted keys
     * @return string The decrypted PEM key
     * @throws \Marzsv\DteSigner\Exceptions\DteSignerException
     */
    public function toPemDecrypted(string $key, ?string $password = null): string;
}
