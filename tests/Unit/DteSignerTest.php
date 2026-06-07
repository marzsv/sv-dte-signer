<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Tests\Unit;

use Marzsv\DteSigner\DteSigner;
use Marzsv\DteSigner\Certificate\CertificateLoader;
use Marzsv\DteSigner\Exceptions\CertificateException;
use Marzsv\DteSigner\Exceptions\DteSignerException;
use Marzsv\DteSigner\Exceptions\ValidationException;
use Marzsv\DteSigner\Signing\JwsSigner;
use Marzsv\DteSigner\Validators\RequestValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DteSignerTest extends TestCase
{
    private DteSigner $signer;
    private RequestValidator&MockObject $mockValidator;
    private CertificateLoader&MockObject $mockLoader;
    private JwsSigner&MockObject $mockJwsSigner;

    protected function setUp(): void
    {
        $this->mockValidator = $this->createMock(RequestValidator::class);
        $this->mockLoader = $this->createMock(CertificateLoader::class);
        $this->mockJwsSigner = $this->createMock(JwsSigner::class);

        $this->signer = new DteSigner(
            'certificates',
            $this->mockValidator,
            $this->mockLoader,
            $this->mockJwsSigner
        );
    }

    public function testSignSuccessful(): void
    {
        // Arrange
        $request = [
            'nit' => '12345678901234',
            'privateKeyPassword' => 'testpassword',
            'dteJson' => ['test' => 'data']
        ];

        $this->mockValidator
            ->expects(self::once())
            ->method('validate')
            ->with($request);

        $this->mockLoader
            ->expects(self::once())
            ->method('loadCertificate')
            ->with('12345678901234', 'testpassword')
            ->willReturn(['privateKey' => 'mock-private-key']);

        $this->mockJwsSigner
            ->expects(self::once())
            ->method('sign')
            ->with(['test' => 'data'], 'mock-private-key', 'testpassword')
            ->willReturn('signed.jws.token');

        // Act
        $response = $this->signer->sign($request);

        // Assert
        self::assertTrue($response['success']);
        self::assertEquals('signed.jws.token', $response['data']);
        self::assertEquals('DTE signed successfully', $response['message']);
    }

    public function testSignValidationError(): void
    {
        // Arrange
        $request = [
            'nit' => '123', // Invalid NIT
            'privateKeyPassword' => 'test',
            'dteJson' => []
        ];

        $this->mockValidator
            ->expects(self::once())
            ->method('validate')
            ->willThrowException(new ValidationException('Validation failed', ['NIT must be 14 digits']));

        // Act
        $response = $this->signer->sign($request);

        // Assert
        self::assertFalse($response['success']);
        self::assertEquals('COD_803', $response['errorCode']);
        $errors = $response['errors'];
        self::assertIsArray($errors);
        self::assertContains('NIT must be 14 digits', $errors);
    }

    public function testSignCertificateNotFound(): void
    {
        // Arrange
        $request = [
            'nit' => '12345678901234',
            'privateKeyPassword' => 'testpassword',
            'dteJson' => ['test' => 'data']
        ];

        $this->mockValidator
            ->expects(self::once())
            ->method('validate');

        $this->mockLoader
            ->expects(self::once())
            ->method('loadCertificate')
            ->willThrowException(CertificateException::certificateNotFound('12345678901234'));

        // Act
        $response = $this->signer->sign($request);

        // Assert
        self::assertFalse($response['success']);
        self::assertEquals('COD_812', $response['errorCode']);
    }

    public function testSignJwsSigningError(): void
    {
        // Arrange
        $request = [
            'nit' => '12345678901234',
            'privateKeyPassword' => 'testpassword',
            'dteJson' => ['test' => 'data']
        ];

        $this->mockValidator
            ->expects(self::once())
            ->method('validate');

        $this->mockLoader
            ->expects(self::once())
            ->method('loadCertificate')
            ->willReturn(['privateKey' => 'mock-private-key']);

        $this->mockJwsSigner
            ->expects(self::once())
            ->method('sign')
            ->willThrowException(new DteSignerException('Signing failed', 'COD_815'));

        // Act
        $response = $this->signer->sign($request);

        // Assert
        self::assertFalse($response['success']);
        self::assertEquals('COD_815', $response['errorCode']);
    }

    public function testSignUnexpectedException(): void
    {
        // Arrange
        $request = [
            'nit' => '12345678901234',
            'privateKeyPassword' => 'testpassword',
            'dteJson' => ['test' => 'data']
        ];

        $this->mockValidator
            ->expects(self::once())
            ->method('validate')
            ->willThrowException(new \RuntimeException('Unexpected error'));

        // Act
        $response = $this->signer->sign($request);

        // Assert
        self::assertFalse($response['success']);
        self::assertEquals('COD_500', $response['errorCode']);
        $message = $response['message'];
        self::assertIsString($message);
        self::assertStringContainsString('Unexpected error', $message);
    }

    public function testSignFromJsonFile(): void
    {
        // Arrange
        $tempFile = sys_get_temp_dir() . '/test_dte_request.json';
        $requestData = [
            'nit' => '12345678901234',
            'privateKeyPassword' => 'testpassword',
            'dteJson' => ['test' => 'data']
        ];
        file_put_contents($tempFile, json_encode($requestData));

        $this->mockValidator
            ->expects(self::once())
            ->method('validate');

        $this->mockLoader
            ->expects(self::once())
            ->method('loadCertificate')
            ->willReturn(['privateKey' => 'mock-private-key']);

        $this->mockJwsSigner
            ->expects(self::once())
            ->method('sign')
            ->willReturn('signed.jws.token');

        // Act
        $response = $this->signer->sign($tempFile);

        // Assert
        self::assertTrue($response['success']);
        self::assertEquals('signed.jws.token', $response['data']);

        // Cleanup
        unlink($tempFile);
    }

    public function testSignFromNonExistentFile(): void
    {
        // Act
        $response = $this->signer->sign('/path/to/nonexistent/file.json');

        // Assert
        self::assertFalse($response['success']);
        self::assertEquals('COD_803', $response['errorCode']);
        $errors = $response['errors'];
        self::assertIsArray($errors);
        self::assertCount(1, $errors);
        self::assertIsString($errors[0]);
        self::assertStringContainsString('File does not exist', $errors[0]);
    }

    public function testSignFromInvalidJsonFile(): void
    {
        // Arrange
        $tempFile = sys_get_temp_dir() . '/invalid_json.json';
        file_put_contents($tempFile, '{"invalid": json}');

        // Act
        $response = $this->signer->sign($tempFile);

        // Assert
        self::assertFalse($response['success']);
        self::assertEquals('COD_803', $response['errorCode']);

        // Cleanup
        unlink($tempFile);
    }

    public function testSignWithDeprecatedPasswordPriField(): void
    {
        // Arrange - using the old field name
        $request = [
            'nit' => '12345678901234',
            'passwordPri' => 'testpassword',
            'dteJson' => ['test' => 'data']
        ];

        $this->mockValidator
            ->expects(self::once())
            ->method('validate')
            ->with(self::callback(function (array $data): bool {
                return isset($data['privateKeyPassword']) && !isset($data['passwordPri']);
            }));

        $this->mockLoader
            ->expects(self::once())
            ->method('loadCertificate')
            ->with('12345678901234', 'testpassword')
            ->willReturn(['privateKey' => 'mock-private-key']);

        $this->mockJwsSigner
            ->expects(self::once())
            ->method('sign')
            ->willReturn('signed.jws.token');

        // Act
        $response = @$this->signer->sign($request); // @ suppresses the deprecation notice for test

        // Assert
        self::assertTrue($response['success']);
        self::assertEquals('signed.jws.token', $response['data']);
    }

    public function testSignWithDeprecatedPasswordPriTriggersDeprecation(): void
    {
        $request = [
            'nit' => '12345678901234',
            'passwordPri' => 'testpassword',
            'dteJson' => ['test' => 'data']
        ];

        $this->mockValidator
            ->expects(self::once())
            ->method('validate');

        $this->mockLoader
            ->expects(self::any())
            ->method('loadCertificate')
            ->willReturn(['privateKey' => 'mock-private-key']);

        $this->mockJwsSigner
            ->expects(self::any())
            ->method('sign')
            ->willReturn('signed.jws.token');

        // Assert deprecation is triggered
        set_error_handler(function (int $errno, string $errstr): bool {
            self::assertEquals(E_USER_DEPRECATED, $errno);
            self::assertStringContainsString('passwordPri', $errstr);
            self::assertStringContainsString('privateKeyPassword', $errstr);
            return true;
        });

        $this->signer->sign($request);

        restore_error_handler();
    }

    public function testSignPrivateKeyPasswordTakesPrecedenceOverPasswordPri(): void
    {
        // If both are provided, privateKeyPassword wins and passwordPri is ignored
        $request = [
            'nit' => '12345678901234',
            'privateKeyPassword' => 'newpassword',
            'passwordPri' => 'oldpassword',
            'dteJson' => ['test' => 'data']
        ];

        $this->mockValidator
            ->expects(self::once())
            ->method('validate');

        $this->mockLoader
            ->expects(self::once())
            ->method('loadCertificate')
            ->with('12345678901234', 'newpassword')
            ->willReturn(['privateKey' => 'mock-private-key']);

        $this->mockJwsSigner
            ->expects(self::once())
            ->method('sign')
            ->willReturn('signed.jws.token');

        // Act - no deprecation should trigger since privateKeyPassword is present
        $response = $this->signer->sign($request);

        // Assert
        self::assertTrue($response['success']);
    }

    public function testConstructorWithDefaultDependencies(): void
    {
        // Act & Assert - construction should not throw
        self::expectNotToPerformAssertions();
        $signer = new DteSigner();
    }

    public function testConstructorWithCustomDirectory(): void
    {
        // Act & Assert - construction should not throw
        self::expectNotToPerformAssertions();
        $signer = new DteSigner('/custom/certificates/path');
    }
}
