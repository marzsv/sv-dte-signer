<?php

declare(strict_types=1);

namespace DteSigner\Tests\Unit;

use DteSigner\Exceptions\DteSignerException;
use DteSigner\Utils\ResponseBuilder;
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
        $this->assertTrue($response['success']);
        $this->assertEquals('DTE signed successfully', $response['message']);
        $this->assertEquals($signedJws, $response['data']);
    }

    public function testSuccessResponseWithCustomMessage(): void
    {
        // Arrange
        $signedJws = 'eyJhbGciOiJSUzUxMiJ9.eyJkYXRhIjoidGVzdCJ9.signature';
        $customMessage = 'Custom success message';

        // Act
        $response = ResponseBuilder::success($signedJws, $customMessage);

        // Assert
        $this->assertTrue($response['success']);
        $this->assertEquals($customMessage, $response['message']);
        $this->assertEquals($signedJws, $response['data']);
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
        $this->assertFalse($response['success']);
        $this->assertEquals('Test error message', $response['message']);
        $this->assertEquals('COD_TEST', $response['errorCode']);
        $this->assertEquals(['Test error detail'], $response['errors']);
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
        $this->assertFalse($response['success']);
        $this->assertEquals($message, $response['message']);
        $this->assertEquals($errorCode, $response['errorCode']);
        $this->assertEquals($errors, $response['errors']);
    }

    public function testGenericErrorResponseWithDefaults(): void
    {
        // Arrange
        $message = 'Generic error';

        // Act
        $response = ResponseBuilder::genericError($message);

        // Assert
        $this->assertFalse($response['success']);
        $this->assertEquals($message, $response['message']);
        $this->assertEquals('COD_500', $response['errorCode']);
        $this->assertEquals([], $response['errors']);
    }
}