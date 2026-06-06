<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Contracts;

/**
 * Contract for verifying JWS signatures and extracting payloads
 */
interface JwsVerifierInterface
{
    /**
     * Verify JWS signature and return payload if valid
     *
     * @param string $jwsToken The JWS token to verify
     * @param string $publicKey The public key to verify against
     * @return array<string, mixed> Array with 'valid' boolean and 'payload' data
     * @throws \Marzsv\DteSigner\Exceptions\VerificationException
     */
    public function verifySignature(string $jwsToken, string $publicKey): array;

    /**
     * Extract payload from JWS token without verifying signature
     *
     * WARNING: This method does not verify the signature. The extracted
     * data should not be trusted without separate signature verification.
     *
     * @param string $jwsToken The JWS token to extract payload from
     * @return array<string, mixed> The decoded payload data
     * @throws \Marzsv\DteSigner\Exceptions\VerificationException
     */
    public function extractPayload(string $jwsToken): array;
}
