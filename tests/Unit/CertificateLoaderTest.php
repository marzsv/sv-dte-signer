<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Tests\Unit;

use Marzsv\DteSigner\Certificate\CertificateLoader;
use Marzsv\DteSigner\Certificate\CertificateParser;
use Marzsv\DteSigner\Exceptions\CertificateException;
use Marzsv\DteSigner\Validators\CertificateValidator;
use PHPUnit\Framework\TestCase;

class CertificateLoaderTest extends TestCase
{
    private string $tempDir;
    private CertificateLoader $loader;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/cert_test_' . uniqid();
        mkdir($this->tempDir);
        $this->loader = new CertificateLoader($this->tempDir);
    }

    protected function tearDown(): void
    {
        // Clean up temp directory
        $files = glob($this->tempDir . '/*');
        if ($files !== false) {
            foreach ($files as $file) {
                unlink($file);
            }
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    public function testLoadCertificateFileNotFound(): void
    {
        // Arrange
        $nit = '12345678901234';

        // Assert
        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage("Certificate file not found for NIT: {$nit}");

        // Act
        $this->loader->loadCertificate($nit, 'password');
    }

    public function testLoadCertificateSuccessful(): void
    {
        // Arrange
        $nit = '12345678901234';
        $this->createMockCertificateFile($nit);

        $mockParser = $this->createMock(CertificateParser::class);
        $mockParser->method('parse')
            ->willReturn([
                'activo' => 'true',
                'verificado' => 'true',
                'privateKey' => 'mock-key',
                'passwordHash' => 'mock-hash'
            ]);

        $mockValidator = $this->createMock(CertificateValidator::class);
        $mockValidator->expects(self::once())
            ->method('validate');

        $loader = new CertificateLoader($this->tempDir, $mockParser, $mockValidator);

        // Act
        $result = $loader->loadCertificate($nit, 'password');

        // Assert
        self::assertEquals('true', $result['activo']);
        self::assertEquals('mock-key', $result['privateKey']);
    }

    public function testLoadCertificateValidationFails(): void
    {
        // Arrange
        $nit = '12345678901234';
        $this->createMockCertificateFile($nit);

        $mockParser = $this->createMock(CertificateParser::class);
        $mockParser->method('parse')
            ->willReturn([
                'activo' => 'false',
                'verificado' => 'true',
                'privateKey' => 'mock-key',
                'passwordHash' => 'mock-hash'
            ]);

        $mockValidator = $this->createMock(CertificateValidator::class);
        $mockValidator->expects(self::once())
            ->method('validate')
            ->willThrowException(CertificateException::invalidCertificate('Certificate is not active'));

        $loader = new CertificateLoader($this->tempDir, $mockParser, $mockValidator);

        // Assert
        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage('Certificate is not active');

        // Act
        $loader->loadCertificate($nit, 'password');
    }

    public function testGetPublicKeyFileNotFound(): void
    {
        // Arrange
        $nit = '99999999999999';

        // Assert
        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage("Certificate file not found for NIT: {$nit}");

        // Act
        $this->loader->getPublicKey($nit);
    }

    public function testGetPublicKeyNoPrivateKeyInCertificate(): void
    {
        // Arrange
        $nit = '12345678901234';
        $this->createMockCertificateFile($nit);

        $mockParser = $this->createMock(CertificateParser::class);
        $mockParser->method('parse')
            ->willReturn([
                'activo' => 'true',
                'verificado' => 'true',
                'privateKey' => '', // Empty private key
                'passwordHash' => 'mock-hash'
            ]);

        $loader = new CertificateLoader($this->tempDir, $mockParser);

        // Assert
        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage('No private key found in certificate');

        // Act
        $loader->getPublicKey($nit);
    }

    public function testConstructorWithDefaultDependencies(): void
    {
        // Act & Assert - construction should not throw
        self::expectNotToPerformAssertions();
        $loader = new CertificateLoader('/some/path');
    }

    public function testCertificatePathBuildingWithTrailingSlash(): void
    {
        // Arrange - directory with trailing slash
        $loaderWithSlash = new CertificateLoader($this->tempDir . '/');
        $nit = '12345678901234';

        // Assert - should throw not found, but path should be correct (no double slash)
        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage("Certificate file not found for NIT: {$nit}");

        // Act
        $loaderWithSlash->loadCertificate($nit, 'password');
    }

    private function createMockCertificateFile(string $nit): void
    {
        $xmlContent = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<CertificadoMH>
    <nit>{$nit}</nit>
    <activo>true</activo>
    <verificado>true</verificado>
    <privateKey>
        <clave>test</clave>
        <encodied>MIIEvgIBADANBgkq</encodied>
    </privateKey>
</CertificadoMH>
XML;

        file_put_contents($this->tempDir . '/' . $nit . '.crt', $xmlContent);
    }
}
