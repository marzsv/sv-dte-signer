<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Exceptions;

/**
 * Exception thrown when certificate operations fail
 */
class CertificateException extends DteSignerException
{
    private const CERTIFICATE_NOT_FOUND_CODE = 'COD_812';
    private const CERTIFICATE_INVALID_CODE = 'COD_813';
    private const PASSWORD_MISMATCH_CODE = 'COD_814';

    public static function certificateNotFound(string $nit): self
    {
        return new self(
            "Certificate file not found for NIT: {$nit}",
            self::CERTIFICATE_NOT_FOUND_CODE
        );
    }

    public static function invalidCertificate(string $reason): self
    {
        return new self(
            "Invalid certificate: {$reason}",
            self::CERTIFICATE_INVALID_CODE
        );
    }

    public static function passwordMismatch(): self
    {
        return new self(
            'Certificate password does not match',
            self::PASSWORD_MISMATCH_CODE
        );
    }
}