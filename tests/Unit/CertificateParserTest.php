<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Tests\Unit;

use Marzsv\DteSigner\Certificate\CertificateParser;
use Marzsv\DteSigner\Exceptions\CertificateException;
use PHPUnit\Framework\TestCase;

class CertificateParserTest extends TestCase
{
    private CertificateParser $parser;

    protected function setUp(): void
    {
        $this->parser = new CertificateParser();
    }

    public function testParseValidMhCertificate(): void
    {
        // Arrange
        $xml = $this->createValidMhCertificateXml();

        // Act
        $result = $this->parser->parse($xml);

        // Assert
        self::assertEquals('true', $result['activo']);
        self::assertEquals('true', $result['verificado']);
        self::assertNotEmpty($result['privateKey']);
        self::assertNotEmpty($result['passwordHash']);
    }

    public function testParseInvalidXmlThrowsException(): void
    {
        // Arrange
        $invalidXml = 'not valid xml';

        // Assert
        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage('Invalid XML format');

        // Act
        $this->parser->parse($invalidXml);
    }

    public function testParseNonMhCertificateThrowsException(): void
    {
        // Arrange
        $nonMhXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<certificate>
    <activo>true</activo>
    <privateKey>key</privateKey>
</certificate>
XML;

        // Assert
        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage('Certificate must be in MH (Ministerio de Hacienda) format');

        // Act
        $this->parser->parse($nonMhXml);
    }

    public function testParseMissingPrivateKeyThrowsException(): void
    {
        // Arrange
        $xmlWithoutKey = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<CertificadoMH>
    <nit>12345678901234</nit>
    <activo>true</activo>
    <verificado>true</verificado>
    <privateKey>
        <clave>test</clave>
    </privateKey>
</CertificadoMH>
XML;

        // Assert
        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage('Private key not found in MH certificate');

        // Act
        $this->parser->parse($xmlWithoutKey);
    }

    public function testParseInactiveCertificate(): void
    {
        // Arrange
        $xml = $this->createMhCertificateXml(activo: 'false');

        // Act
        $result = $this->parser->parse($xml);

        // Assert
        self::assertEquals('false', $result['activo']);
    }

    public function testParseActivoCero(): void
    {
        // Arrange
        $xml = $this->createMhCertificateXml(activo: '0');

        // Act
        $result = $this->parser->parse($xml);

        // Assert
        self::assertEquals('false', $result['activo']);
    }

    public function testParseActivoUno(): void
    {
        // Arrange
        $xml = $this->createMhCertificateXml(activo: '1');

        // Act
        $result = $this->parser->parse($xml);

        // Assert
        self::assertEquals('true', $result['activo']);
    }

    public function testParseEmptyVerificado(): void
    {
        // Arrange - certificado with empty verificado tag <verificado/>
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<CertificadoMH>
    <nit>12345678901234</nit>
    <activo>true</activo>
    <verificado/>
    <privateKey>
        <clave>test</clave>
        <encodied>MIIEvgIBADANBgkqhkiG9w0B</encodied>
    </privateKey>
</CertificadoMH>
XML;

        // Act
        $result = $this->parser->parse($xml);

        // Assert - empty verificado should still return 'false'
        self::assertEquals('false', $result['verificado']);
    }

    public function testParseVerificadoFalse(): void
    {
        // Arrange
        $xml = $this->createMhCertificateXml(verificado: 'false');

        // Act
        $result = $this->parser->parse($xml);

        // Assert
        self::assertEquals('false', $result['verificado']);
    }

    public function testParseCleanPrivateKey(): void
    {
        // Arrange - key with whitespace
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<CertificadoMH>
    <nit>12345678901234</nit>
    <activo>true</activo>
    <verificado>true</verificado>
    <privateKey>
        <clave>test</clave>
        <encodied>
            MIIEvgIBADANBgkq
            hkiG9w0BAQEFAASC
        </encodied>
    </privateKey>
</CertificadoMH>
XML;

        // Act
        $result = $this->parser->parse($xml);

        // Assert - whitespace should be removed
        self::assertEquals('MIIEvgIBADANBgkqhkiG9w0BAQEFAASC', $result['privateKey']);
    }

    public function testPasswordHashIsGenerated(): void
    {
        // Arrange
        $xml = $this->createValidMhCertificateXml();

        // Act
        $result = $this->parser->parse($xml);

        // Assert - hash should be 64 chars (SHA256)
        self::assertNotEmpty($result['passwordHash']);
        $hash = $result['passwordHash'];
        self::assertIsString($hash);
        self::assertEquals(64, strlen($hash));
    }

    public function testPasswordHashIsConsistent(): void
    {
        // Arrange
        $xml = $this->createValidMhCertificateXml();

        // Act
        $result1 = $this->parser->parse($xml);
        $result2 = $this->parser->parse($xml);

        // Assert - same XML should produce same hash
        self::assertEquals($result1['passwordHash'], $result2['passwordHash']);
    }

    private function createValidMhCertificateXml(): string
    {
        return $this->createMhCertificateXml();
    }

    private function createMhCertificateXml(
        string $nit = '12345678901234',
        string $activo = 'true',
        string $verificado = 'true',
        string $privateKey = 'MIIEvgIBADANBgkqhkiG9w0B'
    ): string {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<CertificadoMH>
    <nit>{$nit}</nit>
    <activo>{$activo}</activo>
    <verificado>{$verificado}</verificado>
    <privateKey>
        <clave>test</clave>
        <encodied>{$privateKey}</encodied>
    </privateKey>
</CertificadoMH>
XML;
    }
}
