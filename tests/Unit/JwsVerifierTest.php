<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Tests\Unit;

use Marzsv\DteSigner\Exceptions\VerificationException;
use Marzsv\DteSigner\Signing\JwsVerifier;
use PHPUnit\Framework\TestCase;

class JwsVerifierTest extends TestCase
{
    private JwsVerifier $verifier;

    protected function setUp(): void
    {
        $this->verifier = new JwsVerifier();
    }

    public function testExtractPayloadSuccessful(): void
    {
        // Arrange
        $expectedPayload = ['dte' => 'test', 'amount' => 113.0];
        $payloadJson = json_encode($expectedPayload);
        assert($payloadJson !== false);
        $base64Payload = base64_encode($payloadJson);
        $base64UrlPayload = strtr($base64Payload, '+/', '-_');
        $base64UrlPayload = rtrim($base64UrlPayload, '=');
        
        $header = base64_encode('{"alg":"RS512","typ":"JWT"}');
        $header = strtr($header, '+/', '-_');
        $header = rtrim($header, '=');
        
        $signature = 'test_signature';
        $jwsToken = $header . '.' . $base64UrlPayload . '.' . $signature;

        // Act
        $result = $this->verifier->extractPayload($jwsToken);

        // Assert
        $this->assertEquals($expectedPayload, $result);
    }

    public function testExtractPayloadWithPadding(): void
    {
        // Arrange - Create a payload that needs padding
        $expectedPayload = ['test' => 'a'];
        $payloadJson = json_encode($expectedPayload);
        assert($payloadJson !== false);
        $base64Payload = base64_encode($payloadJson);
        $base64UrlPayload = strtr($base64Payload, '+/', '-_');
        $base64UrlPayload = rtrim($base64UrlPayload, '='); // Remove padding
        
        $header = 'eyJhbGciOiJSUzUxMiIsInR5cCI6IkpXVCJ9';
        $signature = 'test_signature';
        $jwsToken = $header . '.' . $base64UrlPayload . '.' . $signature;

        // Act
        $result = $this->verifier->extractPayload($jwsToken);

        // Assert
        $this->assertEquals($expectedPayload, $result);
    }

    public function testExtractPayloadEmptyToken(): void
    {
        // Arrange
        $jwsToken = '';

        // Act & Assert
        $this->expectException(VerificationException::class);
        $this->expectExceptionMessage('JWS token cannot be empty');
        
        $this->verifier->extractPayload($jwsToken);
    }

    public function testExtractPayloadInvalidFormat(): void
    {
        // Arrange
        $jwsToken = 'invalid.format'; // Only 2 parts instead of 3

        // Act & Assert
        $this->expectException(VerificationException::class);
        $this->expectExceptionMessage('Invalid JWT format: expected 3 parts separated by dots');
        
        $this->verifier->extractPayload($jwsToken);
    }

    public function testExtractPayloadEmptyParts(): void
    {
        // Arrange
        $jwsToken = 'header..signature'; // Empty payload part

        // Act & Assert
        $this->expectException(VerificationException::class);
        $this->expectExceptionMessage('Invalid JWT format: empty payload');
        
        $this->verifier->extractPayload($jwsToken);
    }

    public function testExtractPayloadInvalidBase64(): void
    {
        // Arrange
        $jwsToken = 'header.@invalid@.signature'; // Invalid base64 payload

        // Act & Assert
        $this->expectException(VerificationException::class);
        $this->expectExceptionMessage('Failed to decode JWT payload');
        
        $this->verifier->extractPayload($jwsToken);
    }

    public function testExtractPayloadInvalidJson(): void
    {
        // Arrange
        $invalidJson = 'invalid json';
        $base64Payload = base64_encode($invalidJson);
        $base64UrlPayload = strtr($base64Payload, '+/', '-_');
        $base64UrlPayload = rtrim($base64UrlPayload, '=');
        
        $jwsToken = 'header.' . $base64UrlPayload . '.signature';

        // Act & Assert
        $this->expectException(VerificationException::class);
        $this->expectExceptionMessage('Invalid JSON in JWT payload');
        
        $this->verifier->extractPayload($jwsToken);
    }

    public function testVerifySignatureEmptyToken(): void
    {
        // Arrange
        $jwsToken = '';
        $publicKey = '-----BEGIN PUBLIC KEY-----...-----END PUBLIC KEY-----';

        // Act & Assert
        $this->expectException(VerificationException::class);
        $this->expectExceptionMessage('JWS token cannot be empty');
        
        $this->verifier->verifySignature($jwsToken, $publicKey);
    }

    public function testVerifySignatureEmptyPublicKey(): void
    {
        // Arrange
        $jwsToken = 'header.payload.signature';
        $publicKey = '';

        // Act & Assert
        $this->expectException(VerificationException::class);
        $this->expectExceptionMessage('Public key cannot be empty');
        
        $this->verifier->verifySignature($jwsToken, $publicKey);
    }

    public function testVerifySignatureInvalidJwtFormat(): void
    {
        // Arrange
        $jwsToken = 'invalid.format'; // Only 2 parts
        $publicKey = '-----BEGIN PUBLIC KEY-----...-----END PUBLIC KEY-----';

        // Act & Assert
        $this->expectException(VerificationException::class);
        $this->expectExceptionMessage('Invalid JWT format: expected 3 parts separated by dots');
        
        $this->verifier->verifySignature($jwsToken, $publicKey);
    }

    public function testValidateJwsFormatWithEmptyHeader(): void
    {
        // Arrange
        $jwsToken = '.payload.signature'; // Empty header

        // Act & Assert
        $this->expectException(VerificationException::class);
        $this->expectExceptionMessage('Invalid JWT format: empty header');
        
        $this->verifier->extractPayload($jwsToken);
    }

    public function testValidateJwsFormatWithEmptySignature(): void
    {
        // Arrange
        $jwsToken = 'header.payload.'; // Empty signature

        // Act & Assert
        $this->expectException(VerificationException::class);
        $this->expectExceptionMessage('Invalid JWT format: empty signature');
        
        $this->verifier->extractPayload($jwsToken);
    }

    public function testAddBase64PaddingNotNeeded(): void
    {
        // Arrange
        $base64String = 'dGVzdA=='; // Already has correct padding

        // Act
        $reflection = new \ReflectionClass($this->verifier);
        $method = $reflection->getMethod('addBase64Padding');
        $method->setAccessible(true);
        $result = $method->invoke($this->verifier, $base64String);

        // Assert
        $this->assertEquals($base64String, $result);
    }

    public function testAddBase64PaddingNeeded(): void
    {
        // Arrange
        $base64String = 'dGVzdA'; // Needs 2 padding characters

        // Act
        $reflection = new \ReflectionClass($this->verifier);
        $method = $reflection->getMethod('addBase64Padding');
        $method->setAccessible(true);
        $result = $method->invoke($this->verifier, $base64String);

        // Assert
        $this->assertEquals('dGVzdA==', $result);
    }

    /**
     * Test that verifySignature handles malformed JWT tokens
     */
    public function testVerifySignatureInvalidSignatureFormat(): void
    {
        // Arrange - Create a malformed JWT that will cause Firebase JWT to throw an exception
        $jwsToken = 'eyJhbGciOiJSUzUxMiJ9.invalid_base64.signature';
        $publicKey = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA4f5wg5l2hKsTeNem/V41
fGnJm6gOdrj8ym3rFkEjWT2btYO3yIhmidX39v4Pc5mZEKvIAHU6T9pPnPEixaJ8
7mefP//9/ZY2t8xKBj/aLhfV8EKkD6ck6d1i8WK3y4FI1bWZRy8y1P3TE3cJZjq5
BLM0W9+6UtbW8+0z8rg3i8pKF3ZOZnQQv8r7j8nKhgV3gqHR9j5c6H5KjgZt7LV2
lrZSc1h9Qj6cRqNc4PkQgNOg+i8j8z8T3EcW3YjV+0SHRJvqz8gUiJn8Y0LiA8MZ
ZYJnw1uH4nK3Zl+vz5E1y4V9l8aJsGzZvqXz+5Ck+8i9u5z8JoQ1L7qR4vGp4v7e
8QIDAQAB
-----END PUBLIC KEY-----';

        // Act & Assert - Should throw VerificationException
        $this->expectException(VerificationException::class);
        
        $this->verifier->verifySignature($jwsToken, $publicKey);
    }
}