<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Tests\Unit;

use Marzsv\DteSigner\Exceptions\CertificateException;
use Marzsv\DteSigner\Validators\CertificateValidator;
use PHPUnit\Framework\TestCase;

class CertificateValidatorTest extends TestCase
{
    private CertificateValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new CertificateValidator();
    }

    public function testValidateValidCertificate(): void
    {
        // Arrange
        $certificateData = [
            'activo' => 'true',
            'verificado' => 'true',
            'privateKey' => 'mock-private-key',
            'passwordHash' => 'mock-hash'
        ];

        // Act & Assert - should not throw
        self::expectNotToPerformAssertions();
        $this->validator->validate($certificateData, 'password');
    }

    public function testValidateInactiveCertificateThrowsException(): void
    {
        // Arrange
        $certificateData = [
            'activo' => 'false',
            'verificado' => 'true',
            'privateKey' => 'mock-private-key',
            'passwordHash' => 'mock-hash'
        ];

        // Assert
        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage('Certificate is not active');

        // Act
        $this->validator->validate($certificateData, 'password');
    }

    public function testValidateMissingActivoThrowsException(): void
    {
        // Arrange
        $certificateData = [
            // 'activo' missing
            'verificado' => 'true',
            'privateKey' => 'mock-private-key',
            'passwordHash' => 'mock-hash'
        ];

        // Assert
        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage('Certificate is not active');

        // Act
        $this->validator->validate($certificateData, 'password');
    }

    public function testValidateMissingVerificadoThrowsException(): void
    {
        // Arrange
        $certificateData = [
            'activo' => 'true',
            // 'verificado' missing
            'privateKey' => 'mock-private-key',
            'passwordHash' => 'mock-hash'
        ];

        // Assert
        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage('Certificate is not verified');

        // Act
        $this->validator->validate($certificateData, 'password');
    }

    public function testValidateMissingPrivateKeyThrowsException(): void
    {
        // Arrange
        $certificateData = [
            'activo' => 'true',
            'verificado' => 'true',
            // 'privateKey' missing
            'passwordHash' => 'mock-hash'
        ];

        // Assert
        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage('Private key is missing');

        // Act
        $this->validator->validate($certificateData, 'password');
    }

    public function testValidateEmptyPrivateKeyThrowsException(): void
    {
        // Arrange
        $certificateData = [
            'activo' => 'true',
            'verificado' => 'true',
            'privateKey' => '', // Empty
            'passwordHash' => 'mock-hash'
        ];

        // Assert
        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage('Private key is missing');

        // Act
        $this->validator->validate($certificateData, 'password');
    }

    public function testValidateWithoutPasswordHashSucceeds(): void
    {
        // Arrange - passwordHash is not required because MH certificates
        // don't store password hashes. Validation happens during signing.
        $certificateData = [
            'activo' => 'true',
            'verificado' => 'true',
            'privateKey' => 'mock-private-key',
            // 'passwordHash' not needed
        ];

        // Act & Assert - should NOT throw
        $this->validator->validate($certificateData, 'password');
        self::expectNotToPerformAssertions();
    }

    public function testValidateVerificadoFalseString(): void
    {
        // Arrange - verificado key exists but value is 'false'
        $certificateData = [
            'activo' => 'true',
            'verificado' => 'false', // Key exists
            'privateKey' => 'mock-private-key',
            'passwordHash' => 'mock-hash'
        ];

        // Act & Assert - should NOT throw because key exists
        $this->validator->validate($certificateData, 'password');
        self::expectNotToPerformAssertions();
    }

    public function testValidateVerificadoEmptyString(): void
    {
        // Arrange - verificado key exists but value is empty
        $certificateData = [
            'activo' => 'true',
            'verificado' => '', // Key exists with empty value
            'privateKey' => 'mock-private-key',
            'passwordHash' => 'mock-hash'
        ];

        // Act & Assert - should NOT throw because key exists
        $this->validator->validate($certificateData, 'password');
        self::expectNotToPerformAssertions();
    }

    public function testValidateActivoNotExactlyTrue(): void
    {
        // Arrange - activo is 'TRUE' (uppercase)
        $certificateData = [
            'activo' => 'TRUE', // Uppercase
            'verificado' => 'true',
            'privateKey' => 'mock-private-key',
            'passwordHash' => 'mock-hash'
        ];

        // Assert - should throw because it's not exactly 'true'
        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage('Certificate is not active');

        // Act
        $this->validator->validate($certificateData, 'password');
    }

    public function testValidateWithAnyPassword(): void
    {
        // Arrange
        $certificateData = [
            'activo' => 'true',
            'verificado' => 'true',
            'privateKey' => 'mock-private-key',
            'passwordHash' => 'mock-hash'
        ];

        // Act & Assert - password is not actually validated against hash
        // The real validation happens during OpenSSL decryption
        $this->validator->validate($certificateData, 'any-password');
        $this->validator->validate($certificateData, 'different-password');
        self::expectNotToPerformAssertions();
    }
}
