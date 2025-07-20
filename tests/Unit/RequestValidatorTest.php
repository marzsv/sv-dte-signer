<?php

declare(strict_types=1);

namespace DteSigner\Tests\Unit;

use DteSigner\Exceptions\ValidationException;
use DteSigner\Validators\RequestValidator;
use PHPUnit\Framework\TestCase;

class RequestValidatorTest extends TestCase
{
    private RequestValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new RequestValidator();
    }

    public function testValidateValidRequest(): void
    {
        // Arrange
        $validRequest = [
            'nit' => '12345678901234',
            'passwordPri' => 'validpassword',
            'dteJson' => ['test' => 'data']
        ];

        // Act & Assert
        $this->expectNotToPerformAssertions();
        $this->validator->validate($validRequest);
    }

    public function testValidateInvalidNitLength(): void
    {
        // Arrange
        $invalidRequest = [
            'nit' => '123456789', // Too short
            'passwordPri' => 'validpassword',
            'dteJson' => ['test' => 'data']
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validate($invalidRequest);
    }

    public function testValidateInvalidNitFormat(): void
    {
        // Arrange
        $invalidRequest = [
            'nit' => '1234567890123a', // Contains letter
            'passwordPri' => 'validpassword',
            'dteJson' => ['test' => 'data']
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validate($invalidRequest);
    }

    public function testValidateShortPassword(): void
    {
        // Arrange
        $invalidRequest = [
            'nit' => '12345678901234',
            'passwordPri' => 'short', // Too short
            'dteJson' => ['test' => 'data']
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validate($invalidRequest);
    }

    public function testValidateMissingRequiredField(): void
    {
        // Arrange
        $invalidRequest = [
            'nit' => '12345678901234',
            // Missing passwordPri
            'dteJson' => ['test' => 'data']
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validate($invalidRequest);
    }

    public function testValidateNullDteJson(): void
    {
        // Arrange
        $invalidRequest = [
            'nit' => '12345678901234',
            'passwordPri' => 'validpassword',
            'dteJson' => null
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validate($invalidRequest);
    }
}