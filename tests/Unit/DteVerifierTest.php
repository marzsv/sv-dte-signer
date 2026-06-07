<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Tests\Unit;

use Marzsv\DteSigner\Certificate\CertificateLoader;
use Marzsv\DteSigner\DteVerifier;
use Marzsv\DteSigner\Exceptions\CertificateException;
use Marzsv\DteSigner\Exceptions\VerificationException;
use Marzsv\DteSigner\Signing\JwsVerifier;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DteVerifierTest extends TestCase
{
    private CertificateLoader&MockObject $certificateLoader;
    private JwsVerifier&MockObject $jwsVerifier;
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
            ->expects(self::once())
            ->method('getPublicKey')
            ->with($nit)
            ->willReturn($publicKey);

        $this->jwsVerifier
            ->expects(self::once())
            ->method('verifySignature')
            ->with($jwsToken, $publicKey)
            ->willReturn([
                'valid' => true,
                'payload' => $expectedPayload
            ]);

        // Act
        $response = $this->verifier->verify($jwsToken, $nit);

        // Assert
        self::assertTrue($response['success']);
        self::assertEquals('DTE signature verified successfully', $response['message']);
        self::assertEquals($expectedPayload, $response['data']);
    }

    public function testVerifyInvalidSignature(): void
    {
        // Arrange
        $jwsToken = 'invalid.jwt.token';
        $nit = '12345678901234';
        $publicKey = '-----BEGIN PUBLIC KEY-----...-----END PUBLIC KEY-----';

        $this->certificateLoader
            ->expects(self::once())
            ->method('getPublicKey')
            ->with($nit)
            ->willReturn($publicKey);

        $this->jwsVerifier
            ->expects(self::once())
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
        self::assertFalse($response['success']);
        $message = $response['message'];
        self::assertIsString($message);
        self::assertStringContainsString('Invalid JWS signature', $message);
        self::assertEquals('COD_820', $response['errorCode']);
    }

    public function testVerifyEmptyJwsToken(): void
    {
        // Arrange
        $jwsToken = '';
        $nit = '12345678901234';

        // Act
        $response = $this->verifier->verify($jwsToken, $nit);

        // Assert
        self::assertFalse($response['success']);
        $message = $response['message'];
        self::assertIsString($message);
        self::assertStringContainsString('JWS token cannot be empty', $message);
        self::assertEquals('COD_820', $response['errorCode']);
    }

    public function testVerifyInvalidNit(): void
    {
        // Arrange
        $jwsToken = 'eyJhbGciOiJSUzUxMiJ9.eyJkdGUiOiJ0ZXN0In0.signature';
        $invalidNit = '123';

        // Act
        $response = $this->verifier->verify($jwsToken, $invalidNit);

        // Assert
        self::assertFalse($response['success']);
        $message = $response['message'];
        self::assertIsString($message);
        self::assertStringContainsString('Invalid NIT format', $message);
        self::assertEquals('COD_820', $response['errorCode']);
    }

    public function testVerifyCertificateNotFound(): void
    {
        // Arrange
        $jwsToken = 'eyJhbGciOiJSUzUxMiJ9.eyJkdGUiOiJ0ZXN0In0.signature';
        $nit = '12345678901234';

        $this->certificateLoader
            ->expects(self::once())
            ->method('getPublicKey')
            ->with($nit)
            ->willThrowException(CertificateException::certificateNotFound($nit));

        // Act
        $response = $this->verifier->verify($jwsToken, $nit);

        // Assert
        self::assertFalse($response['success']);
        self::assertEquals('COD_812', $response['errorCode']);
    }

    public function testExtractPayloadSuccessful(): void
    {
        // Arrange
        $jwsToken = 'eyJhbGciOiJSUzUxMiJ9.eyJkdGUiOiJ0ZXN0In0.signature';
        $expectedPayload = ['dte' => 'test'];

        $this->jwsVerifier
            ->expects(self::once())
            ->method('extractPayload')
            ->with($jwsToken)
            ->willReturn($expectedPayload);

        // Act
        $response = $this->verifier->extractPayload($jwsToken);

        // Assert
        self::assertTrue($response['success']);
        $message = $response['message'];
        self::assertIsString($message);
        self::assertStringContainsString('signature not verified', $message);
        self::assertEquals($expectedPayload, $response['data']);
    }

    public function testExtractPayloadEmptyToken(): void
    {
        // Arrange
        $jwsToken = '';

        // Act
        $response = $this->verifier->extractPayload($jwsToken);

        // Assert
        self::assertFalse($response['success']);
        $message = $response['message'];
        self::assertIsString($message);
        self::assertStringContainsString('JWS token cannot be empty', $message);
        self::assertEquals('COD_820', $response['errorCode']);
    }

    public function testExtractPayloadJwsVerifierException(): void
    {
        // Arrange
        $jwsToken = 'invalid.jwt.token';

        $this->jwsVerifier
            ->expects(self::once())
            ->method('extractPayload')
            ->with($jwsToken)
            ->willThrowException(new VerificationException(
                'Invalid JWT format',
                ['JWT format error']
            ));

        // Act
        $response = $this->verifier->extractPayload($jwsToken);

        // Assert
        self::assertFalse($response['success']);
        $message = $response['message'];
        self::assertIsString($message);
        self::assertStringContainsString('Invalid JWT format', $message);
        self::assertEquals('COD_820', $response['errorCode']);
    }

    public function testVerifyUnexpectedException(): void
    {
        // Arrange
        $jwsToken = 'eyJhbGciOiJSUzUxMiJ9.eyJkdGUiOiJ0ZXN0In0.signature';
        $nit = '12345678901234';

        $this->certificateLoader
            ->expects(self::once())
            ->method('getPublicKey')
            ->with($nit)
            ->willThrowException(new \RuntimeException('Unexpected error'));

        // Act
        $response = $this->verifier->verify($jwsToken, $nit);

        // Assert
        self::assertFalse($response['success']);
        $message = $response['message'];
        self::assertIsString($message);
        self::assertStringContainsString('Unexpected verification error', $message);
        self::assertEquals('COD_500', $response['errorCode']);
    }

    public function testExtractPayloadUnexpectedException(): void
    {
        // Arrange
        $jwsToken = 'eyJhbGciOiJSUzUxMiJ9.eyJkdGUiOiJ0ZXN0In0.signature';

        $this->jwsVerifier
            ->expects(self::once())
            ->method('extractPayload')
            ->with($jwsToken)
            ->willThrowException(new \RuntimeException('Unexpected error'));

        // Act
        $response = $this->verifier->extractPayload($jwsToken);

        // Assert
        self::assertFalse($response['success']);
        $message = $response['message'];
        self::assertIsString($message);
        self::assertStringContainsString('Unexpected extraction error', $message);
        self::assertEquals('COD_500', $response['errorCode']);
    }
}