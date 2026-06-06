<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Contracts;

/**
 * Contract for loading and validating certificates
 */
interface CertificateLoaderInterface
{
    /**
     * Load and validate certificate for the given NIT
     *
     * @return array<string, mixed>
     * @throws \Marzsv\DteSigner\Exceptions\CertificateException
     */
    public function loadCertificate(string $nit, string $password): array;

    /**
     * Get public key for verification from certificate
     *
     * @throws \Marzsv\DteSigner\Exceptions\CertificateException
     */
    public function getPublicKey(string $nit): string;
}
