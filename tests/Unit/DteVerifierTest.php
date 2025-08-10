<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Tests\Unit;

use Marzsv\DteSigner\Certificate\CertificateLoader;
use Marzsv\DteSigner\DteVerifier;
use Marzsv\DteSigner\Exceptions\CertificateException;
use Marzsv\DteSigner\Exceptions\VerificationException;
use Marzsv\DteSigner\Signing\JwsVerifier;
use PHPUnit\Framework\TestCase;

class DteVerifierTest extends TestCase
{
    private CertificateLoader $certificateLoader;
    private JwsVerifier $jwsVerifier;
    private DteVerifier $verifier;

    protected function setUp(): void
    {
        $this->certificateLoader = $this->createMock(CertificateLoader::class);
        $this->jwsVerifier = $this->createMock(JwsVerifier::class);
        $this->verifier = new DteVerifier(
            'test-certificates',
            $this->certificateLoader,
            $this->jwsVerifier
        );
    }

    public function testVerifySuccessful(): void
    {
        // Arrange
        $jwsToken = 'eyJhbGciOiJSUzUxMiJ9.eyJkdGUiOiJ0ZXN0In0.signature';
        $nit = '12345678901234';
        $publicKey = '-----BEGIN PUBLIC KEY-----...-----END PUBLIC KEY-----';
        $expectedPayload = ['dte' => 'test'];

        $this->certificateLoader
            ->expects($this->once())
            ->method('getPublicKey')
            ->with($nit)
            ->willReturn($publicKey);

        $this->jwsVerifier
            ->expects($this->once())
            ->method('verifySignature')
            ->with($jwsToken, $publicKey)
            ->willReturn([
                'valid' => true,
                'payload' => $expectedPayload
            ]);

        // Act
        $response = $this->verifier->verify($jwsToken, $nit);

        // Assert
        $this->assertTrue($response['success']);
        $this->assertEquals('DTE signature verified successfully', $response['message']);
        $this->assertEquals($expectedPayload, $response['data']);
    }

    public function testVerifyInvalidSignature(): void
    {
        // Arrange
        $jwsToken = 'invalid.jwt.token';
        $nit = '12345678901234';
        $publicKey = '-----BEGIN PUBLIC KEY-----...-----END PUBLIC KEY-----';

        $this->certificateLoader
            ->expects($this->once())
            ->method('getPublicKey')
            ->with($nit)
            ->willReturn($publicKey);

        $this->jwsVerifier
            ->expects($this->once())
            ->method('verifySignature')
            ->with($jwsToken, $publicKey)
            ->willReturn([
                'valid' => false,
                'payload' => null,
                'error' => 'Invalid signature'
            ]);

        // Act
        $response = $this->verifier->verify($jwsToken, $nit);

        // Assert
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('Invalid JWS signature', $response['message']);
        $this->assertEquals('COD_820', $response['errorCode']);
    }

    public function testVerifyEmptyJwsToken(): void
    {
        // Arrange
        $jwsToken = '';
        $nit = '12345678901234';

        // Act
        $response = $this->verifier->verify($jwsToken, $nit);

        // Assert
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('JWS token cannot be empty', $response['message']);
        $this->assertEquals('COD_820', $response['errorCode']);
    }

    public function testVerifyInvalidNit(): void
    {
        // Arrange
        $jwsToken = 'eyJhbGciOiJSUzUxMiJ9.eyJkdGUiOiJ0ZXN0In0.signature';
        $invalidNit = '123';

        // Act
        $response = $this->verifier->verify($jwsToken, $invalidNit);

        // Assert
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('Invalid NIT format', $response['message']);
        $this->assertEquals('COD_820', $response['errorCode']);
    }

    public function testVerifyCertificateNotFound(): void
    {
        // Arrange
        $jwsToken = 'eyJhbGciOiJSUzUxMiJ9.eyJkdGUiOiJ0ZXN0In0.signature';
        $nit = '12345678901234';

        $this->certificateLoader
            ->expects($this->once())
            ->method('getPublicKey')
            ->with($nit)
            ->willThrowException(CertificateException::certificateNotFound($nit));

        // Act
        $response = $this->verifier->verify($jwsToken, $nit);

        // Assert
        $this->assertFalse($response['success']);
        $this->assertEquals('COD_812', $response['errorCode']);
    }

    public function testExtractPayloadSuccessful(): void
    {
        // Arrange
        $jwsToken = 'eyJhbGciOiJSUzUxMiJ9.eyJkdGUiOiJ0ZXN0In0.signature';
        $expectedPayload = ['dte' => 'test'];

        $this->jwsVerifier
            ->expects($this->once())
            ->method('extractPayload')
            ->with($jwsToken)
            ->willReturn($expectedPayload);

        // Act
        $response = $this->verifier->extractPayload($jwsToken);

        // Assert
        $this->assertTrue($response['success']);
        $this->assertStringContainsString('signature not verified', $response['message']);
        $this->assertEquals($expectedPayload, $response['data']);
    }

    public function testExtractPayloadEmptyToken(): void
    {
        // Arrange
        $jwsToken = '';

        // Act
        $response = $this->verifier->extractPayload($jwsToken);

        // Assert
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('JWS token cannot be empty', $response['message']);
        $this->assertEquals('COD_820', $response['errorCode']);
    }

    public function testExtractPayloadJwsVerifierException(): void
    {
        // Arrange
        $jwsToken = 'invalid.jwt.token';

        $this->jwsVerifier
            ->expects($this->once())
            ->method('extractPayload')
            ->with($jwsToken)
            ->willThrowException(new VerificationException(
                'Invalid JWT format',
                ['JWT format error']
            ));

        // Act
        $response = $this->verifier->extractPayload($jwsToken);

        // Assert
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('Invalid JWT format', $response['message']);
        $this->assertEquals('COD_820', $response['errorCode']);
    }

    public function testVerifyUnexpectedException(): void
    {
        // Arrange
        $jwsToken = 'eyJhbGciOiJSUzUxMiJ9.eyJkdGUiOiJ0ZXN0In0.signature';
        $nit = '12345678901234';

        $this->certificateLoader
            ->expects($this->once())
            ->method('getPublicKey')
            ->with($nit)
            ->willThrowException(new \RuntimeException('Unexpected error'));

        // Act
        $response = $this->verifier->verify($jwsToken, $nit);

        // Assert
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('Unexpected verification error', $response['message']);
        $this->assertEquals('COD_500', $response['errorCode']);
    }

    public function testExtractPayloadUnexpectedException(): void
    {
        // Arrange
        $jwsToken = 'eyJhbGciOiJSUzUxMiJ9.eyJkdGUiOiJ0ZXN0In0.signature';

        $this->jwsVerifier
            ->expects($this->once())
            ->method('extractPayload')
            ->with($jwsToken)
            ->willThrowException(new \RuntimeException('Unexpected error'));

        // Act
        $response = $this->verifier->extractPayload($jwsToken);

        // Assert
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('Unexpected extraction error', $response['message']);
        $this->assertEquals('COD_500', $response['errorCode']);
    }
}