<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Tests\Unit;

use Marzsv\DteSigner\Exceptions\DteSignerException;
use Marzsv\DteSigner\Signing\JwsSigner;
use PHPUnit\Framework\TestCase;

class JwsSignerTest extends TestCase
{
    private JwsSigner $signer;
    private string $testPrivateKey = '';

    protected function setUp(): void
    {
        $this->signer = new JwsSigner();

        // Generate a test RSA key pair for testing
        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $resource = openssl_pkey_new($config);
        $privateKey = '';
        openssl_pkey_export($resource, $privateKey);
        $this->testPrivateKey = $privateKey;
    }

    public function testSignSuccessful(): void
    {
        // Arrange
        $dteJson = [
            'identificacion' => ['tipoDte' => '01'],
            'emisor' => ['nit' => '12345678901234']
        ];

        // Act
        $result = $this->signer->sign($dteJson, $this->testPrivateKey);

        // Assert
        $this->assertIsString($result);
        $this->assertNotEmpty($result);

        // Verify JWS format (header.payload.signature)
        $parts = explode('.', $result);
        $this->assertCount(3, $parts);
    }

    public function testSignEmptyPrivateKeyThrowsException(): void
    {
        // Arrange
        $dteJson = ['test' => 'data'];

        // Assert
        $this->expectException(DteSignerException::class);
        $this->expectExceptionMessage('Private key cannot be empty');

        // Act
        $this->signer->sign($dteJson, '');
    }

    public function testSignEmptyDteJsonThrowsException(): void
    {
        // Assert
        $this->expectException(DteSignerException::class);
        $this->expectExceptionMessage('DTE JSON cannot be empty');

        // Act
        $this->signer->sign([], $this->testPrivateKey);
    }

    public function testSignWithPemFormattedKey(): void
    {
        // Arrange
        $dteJson = ['test' => 'data'];

        // Act
        $result = $this->signer->sign($dteJson, $this->testPrivateKey);

        // Assert
        $this->assertIsString($result);
        $parts = explode('.', $result);
        $this->assertCount(3, $parts);
    }

    public function testSignWithBase64EncodedKey(): void
    {
        // Arrange
        $dteJson = ['test' => 'data'];

        // Extract the key content without PEM headers and convert to base64
        $keyContent = str_replace(
            ['-----BEGIN PRIVATE KEY-----', '-----END PRIVATE KEY-----', "\n"],
            '',
            $this->testPrivateKey
        );
        // The key is already base64, but we need to decode and re-encode as DER
        $derKey = base64_decode($keyContent);
        $base64Key = base64_encode($derKey);

        // Act
        $result = $this->signer->sign($dteJson, $base64Key);

        // Assert
        $this->assertIsString($result);
        $parts = explode('.', $result);
        $this->assertCount(3, $parts);
    }

    public function testSignInvalidBase64KeyThrowsException(): void
    {
        // Arrange
        $dteJson = ['test' => 'data'];
        $invalidKey = 'not-a-valid-base64-or-pem-key!!!';

        // Assert
        $this->expectException(DteSignerException::class);

        // Act
        $this->signer->sign($dteJson, $invalidKey);
    }

    public function testSignProducesValidJwtHeader(): void
    {
        // Arrange
        $dteJson = ['test' => 'data'];

        // Act
        $result = $this->signer->sign($dteJson, $this->testPrivateKey);

        // Assert
        $parts = explode('.', $result);
        $header = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);

        $this->assertEquals('RS512', $header['alg']);
        $this->assertEquals('JWT', $header['typ']);
    }

    public function testSignProducesValidPayload(): void
    {
        // Arrange
        $dteJson = [
            'identificacion' => ['tipoDte' => '01'],
            'emisor' => ['nit' => '12345678901234']
        ];

        // Act
        $result = $this->signer->sign($dteJson, $this->testPrivateKey);

        // Assert
        $parts = explode('.', $result);
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        $this->assertEquals($dteJson, $payload);
    }

    public function testSignWithNullPassword(): void
    {
        // Arrange
        $dteJson = ['test' => 'data'];

        // Act
        $result = $this->signer->sign($dteJson, $this->testPrivateKey, null);

        // Assert
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testSignWithComplexDteJson(): void
    {
        // Arrange
        $dteJson = [
            'identificacion' => [
                'version' => 1,
                'ambiente' => '00',
                'tipoDte' => '01',
                'numeroControl' => 'DTE-01-00000001-000000000000001',
                'codigoGeneracion' => 'A1B2C3D4-E5F6-7890-1234-567890ABCDEF'
            ],
            'emisor' => [
                'nit' => '12345678901234',
                'nombre' => 'EMPRESA DE EJEMPLO S.A. DE C.V.'
            ],
            'receptor' => [
                'nit' => '98765432109876',
                'nombre' => 'CLIENTE EJEMPLO S.A. DE C.V.'
            ],
            'cuerpoDocumento' => [
                [
                    'numItem' => 1,
                    'descripcion' => 'Servicio de consultoría',
                    'cantidad' => 1,
                    'precioUni' => 100.00
                ]
            ],
            'resumen' => [
                'totalPagar' => 113.00
            ]
        ];

        // Act
        $result = $this->signer->sign($dteJson, $this->testPrivateKey);

        // Assert
        $this->assertIsString($result);
        $parts = explode('.', $result);
        $this->assertCount(3, $parts);

        // Verify payload integrity
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        $this->assertEquals($dteJson['resumen']['totalPagar'], $payload['resumen']['totalPagar']);
    }
}
