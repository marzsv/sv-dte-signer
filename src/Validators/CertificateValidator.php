<?php

declare(strict_types=1);

namespace DteSigner\Validators;

use DteSigner\Exceptions\CertificateException;

/**
 * Validates XML certificates according to DTE specifications
 */
class CertificateValidator
{
    /**
     * Validate certificate data from parsed XML
     * 
     * @param array<string, mixed> $certificateData
     * @throws CertificateException
     */
    public function validate(array $certificateData, string $providedPassword): void
    {
        $this->validateCertificateStatus($certificateData);
        $this->validatePrivateKey($certificateData);
        $this->validatePassword($certificateData, $providedPassword);
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

        if (!isset($certificateData['verificado']) || $certificateData['verificado'] !== 'true') {
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

    /**
     * Validate password matches the stored hash
     * 
     * @param array<string, mixed> $certificateData
     */
    private function validatePassword(array $certificateData, string $providedPassword): void
    {
        if (!isset($certificateData['passwordHash'])) {
            throw CertificateException::invalidCertificate('Password hash is missing');
        }

        $hashedPassword = hash('sha512', $providedPassword);

        if ($hashedPassword !== $certificateData['passwordHash']) {
            throw CertificateException::passwordMismatch();
        }
    }
}