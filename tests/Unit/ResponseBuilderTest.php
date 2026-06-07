<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Tests\Unit;

use Marzsv\DteSigner\Exceptions\DteSignerException;
use Marzsv\DteSigner\Utils\ResponseBuilder;
use PHPUnit\Framework\TestCase;

class ResponseBuilderTest extends TestCase
{
    public function testSuccessResponse(): void
    {
        // Arrange
        $signedJws = 'eyJhbGciOiJSUzUxMiJ9.eyJkYXRhIjoidGVzdCJ9.signature';

        // Act
        $response = ResponseBuilder::success($signedJws);

        // Assert
        self::assertTrue($response['success']);
        self::assertEquals('DTE signed successfully', $response['message']);
        self::assertEquals($signedJws, $response['data']);
    }

    public function testSuccessResponseWithCustomMessage(): void
    {
        // Arrange
        $signedJws = 'eyJhbGciOiJSUzUxMiJ9.eyJkYXRhIjoidGVzdCJ9.signature';
        $customMessage = 'Custom success message';

        // Act
        $response = ResponseBuilder::success($signedJws, $customMessage);

        // Assert
        self::assertTrue($response['success']);
        self::assertEquals($customMessage, $response['message']);
        self::assertEquals($signedJws, $response['data']);
    }

    public function testErrorResponse(): void
    {
        // Arrange
        $exception = new DteSignerException(
            'Test error message',
            'COD_TEST',
            ['Test error detail']
        );

        // Act
        $response = ResponseBuilder::error($exception);

        // Assert
        self::assertFalse($response['success']);
        self::assertEquals('Test error message', $response['message']);
        self::assertEquals('COD_TEST', $response['errorCode']);
        self::assertEquals(['Test error detail'], $response['errors']);
    }

    public function testGenericErrorResponse(): void
    {
        // Arrange
        $message = 'Generic error';
        $errorCode = 'COD_GENERIC';
        $errors = ['Error 1', 'Error 2'];

        // Act
        $response = ResponseBuilder::genericError($message, $errorCode, $errors);

        // Assert
        self::assertFalse($response['success']);
        self::assertEquals($message, $response['message']);
        self::assertEquals($errorCode, $response['errorCode']);
        self::assertEquals($errors, $response['errors']);
    }

    public function testGenericErrorResponseWithDefaults(): void
    {
        // Arrange
        $message = 'Generic error';

        // Act
        $response = ResponseBuilder::genericError($message);

        // Assert
        self::assertFalse($response['success']);
        self::assertEquals($message, $response['message']);
        self::assertEquals('COD_500', $response['errorCode']);
        self::assertEquals([], $response['errors']);
    }

    public function testSuccessResponseWithCertificateDates(): void
    {
        // Arrange
        $signedJws = 'eyJhbGciOiJSUzUxMiJ9.eyJkYXRhIjoidGVzdCJ9.signature';
        $certificateDates = [
            'notBefore' => '2025-01-01T00:00:00',
            'notAfter' => '2026-01-01T00:00:00',
        ];

        // Act
        $response = ResponseBuilder::success($signedJws, 'DTE signed successfully', $certificateDates);

        // Assert
        self::assertTrue($response['success']);
        self::assertEquals($signedJws, $response['data']);
        self::assertEquals('2025-01-01T00:00:00', $response['notBefore']);
        self::assertEquals('2026-01-01T00:00:00', $response['notAfter']);
    }

    public function testSuccessResponseWithoutCertificateDates(): void
    {
        // Arrange
        $signedJws = 'eyJhbGciOiJSUzUxMiJ9.eyJkYXRhIjoidGVzdCJ9.signature';

        // Act
        $response = ResponseBuilder::success($signedJws);

        // Assert
        self::assertTrue($response['success']);
        self::assertArrayNotHasKey('notBefore', $response);
        self::assertArrayNotHasKey('notAfter', $response);
    }

    public function testVerificationSuccessResponse(): void
    {
        // Arrange
        $payload = ['dte' => 'test', 'amount' => 113.0];
        $message = 'DTE signature verified successfully';

        // Act
        $response = ResponseBuilder::verificationSuccess($payload, $message);

        // Assert
        self::assertTrue($response['success']);
        self::assertEquals($message, $response['message']);
        self::assertEquals($payload, $response['data']);
    }
}