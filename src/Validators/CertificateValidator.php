<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Validators;

use Marzsv\DteSigner\Exceptions\CertificateException;

/**
 * Validates MH certificates according to DTE specifications
 *
 * Note: Password validation is intentionally NOT performed here.
 * MH certificates don't store password hashes. The actual password
 * validation happens during JWS signing when OpenSSL decrypts the
 * private key - if the password is wrong, decryption will fail.
 */
class CertificateValidator
{
    /**
     * Validate certificate data from parsed XML
     *
     * @param array<string, mixed> $certificateData
     * @param string $providedPassword Unused here - validated during JWS signing
     * @throws CertificateException
     */
    public function validate(array $certificateData, string $providedPassword): void
    {
        $this->validateCertificateStatus($certificateData);
        $this->validatePrivateKey($certificateData);
        // Password validation occurs during JWS signing (OpenSSL decryption)
    }

    /**
     * Validate certificate is active and verified
     *
     * @param array<string, mixed> $certificateData
     */
    private function validateCertificateStatus(array $certificateData): void
    {
        if (!isset($certificateData['activo']) || $certificateData['activo'] !== 'true') {
            throw CertificateException::invalidCertificate('Certificate is not active');
        }

        // For 'verificado': only check that the XML tag exists
        // (can have value 'true' or be empty like '<verificado/>')
        if (!array_key_exists('verificado', $certificateData)) {
            throw CertificateException::invalidCertificate('Certificate is not verified');
        }
    }

    /**
     * Validate private key is present
     *
     * @param array<string, mixed> $certificateData
     */
    private function validatePrivateKey(array $certificateData): void
    {
        if (!isset($certificateData['privateKey']) || empty($certificateData['privateKey'])) {
            throw CertificateException::invalidCertificate('Private key is missing');
        }
    }
}