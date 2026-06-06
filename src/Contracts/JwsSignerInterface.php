<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Contracts;

/**
 * Contract for creating JWS signatures
 */
interface JwsSignerInterface
{
    /**
     * Create a JWS signature for DTE JSON
     *
     * @param array<string, mixed> $dteJson The DTE data to sign
     * @param string $privateKey The private key in PEM or base64-DER format
     * @param string|null $password Optional password for encrypted private key
     * @return string The JWS token (header.payload.signature)
     * @throws \Marzsv\DteSigner\Exceptions\DteSignerException
     */
    public function sign(array $dteJson, string $privateKey, ?string $password = null): string;
}
