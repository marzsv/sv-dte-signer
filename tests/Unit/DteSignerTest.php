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
use PHPUnit\Framework\TestCase;

class DteSignerTest extends TestCase
{
    private DteSigner $signer;
    private RequestValidator $mockValidator;
    private CertificateLoader $mockLoader;
    private JwsSigner $mockJwsSigner;

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
            ->expects($this->once())
            ->method('validate')
            ->with($request);

        $this->mockLoader
            ->expects($this->once())
            ->method('loadCertificate')
            ->with('12345678901234', 'testpassword')
            ->willReturn(['privateKey' => 'mock-private-key']);

        $this->mockJwsSigner
            ->expects($this->once())
            ->method('sign')
            ->with(['test' => 'data'], 'mock-private-key', 'testpassword')
            ->willReturn('signed.jws.token');

        // Act
        $response = $this->signer->sign($request);

        // Assert
        $this->assertTrue($response['success']);
        $this->assertEquals('signed.jws.token', $response['data']);
        $this->assertEquals('DTE signed successfully', $response['message']);
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
            ->expects($this->once())
            ->method('validate')
            ->willThrowException(new ValidationException('Validation failed', ['NIT must be 14 digits']));

        // Act
        $response = $this->signer->sign($request);

        // Assert
        $this->assertFalse($response['success']);
        $this->assertEquals('COD_803', $response['errorCode']);
        $errors = $response['errors'];
        $this->assertIsArray($errors);
        $this->assertContains('NIT must be 14 digits', $errors);
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
            ->expects($this->once())
            ->method('validate');

        $this->mockLoader
            ->expects($this->once())
            ->method('loadCertificate')
            ->willThrowException(CertificateException::certificateNotFound('12345678901234'));

        // Act
        $response = $this->signer->sign($request);

        // Assert
        $this->assertFalse($response['success']);
        $this->assertEquals('COD_812', $response['errorCode']);
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
            ->expects($this->once())
            ->method('validate');

        $this->mockLoader
            ->expects($this->once())
            ->method('loadCertificate')
            ->willReturn(['privateKey' => 'mock-private-key']);

        $this->mockJwsSigner
            ->expects($this->once())
            ->method('sign')
            ->willThrowException(new DteSignerException('Signing failed', 'COD_815'));

        // Act
        $response = $this->signer->sign($request);

        // Assert
        $this->assertFalse($response['success']);
        $this->assertEquals('COD_815', $response['errorCode']);
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
            ->expects($this->once())
            ->method('validate')
            ->willThrowException(new \RuntimeException('Unexpected error'));

        // Act
        $response = $this->signer->sign($request);

        // Assert
        $this->assertFalse($response['success']);
        $this->assertEquals('COD_500', $response['errorCode']);
        $message = $response['message'];
        $this->assertIsString($message);
        $this->assertStringContainsString('Unexpected error', $message);
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
            ->expects($this->once())
            ->method('validate');

        $this->mockLoader
            ->expects($this->once())
            ->method('loadCertificate')
            ->willReturn(['privateKey' => 'mock-private-key']);

        $this->mockJwsSigner
            ->expects($this->once())
            ->method('sign')
            ->willReturn('signed.jws.token');

        // Act
        $response = $this->signer->sign($tempFile);

        // Assert
        $this->assertTrue($response['success']);
        $this->assertEquals('signed.jws.token', $response['data']);

        // Cleanup
        unlink($tempFile);
    }

    public function testSignFromNonExistentFile(): void
    {
        // Act
        $response = $this->signer->sign('/path/to/nonexistent/file.json');

        // Assert
        $this->assertFalse($response['success']);
        $this->assertEquals('COD_803', $response['errorCode']);
        $errors = $response['errors'];
        $this->assertIsArray($errors);
        $this->assertCount(1, $errors);
        $this->assertStringContainsString('File does not exist', $errors[0]);
    }

    public function testSignFromInvalidJsonFile(): void
    {
        // Arrange
        $tempFile = sys_get_temp_dir() . '/invalid_json.json';
        file_put_contents($tempFile, '{"invalid": json}');

        // Act
        $response = $this->signer->sign($tempFile);

        // Assert
        $this->assertFalse($response['success']);
        $this->assertEquals('COD_803', $response['errorCode']);

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
            ->expects($this->once())
            ->method('validate')
            ->with($this->callback(function (array $data): bool {
                return isset($data['privateKeyPassword']) && !isset($data['passwordPri']);
            }));

        $this->mockLoader
            ->expects($this->once())
            ->method('loadCertificate')
            ->with('12345678901234', 'testpassword')
            ->willReturn(['privateKey' => 'mock-private-key']);

        $this->mockJwsSigner
            ->expects($this->once())
            ->method('sign')
            ->willReturn('signed.jws.token');

        // Act
        $response = @$this->signer->sign($request); // @ suppresses the deprecation notice for test

        // Assert
        $this->assertTrue($response['success']);
        $this->assertEquals('signed.jws.token', $response['data']);
    }

    public function testSignWithDeprecatedPasswordPriTriggersDeprecation(): void
    {
        $request = [
            'nit' => '12345678901234',
            'passwordPri' => 'testpassword',
            'dteJson' => ['test' => 'data']
        ];

        $this->mockValidator
            ->expects($this->once())
            ->method('validate');

        $this->mockLoader
            ->expects($this->any())
            ->method('loadCertificate')
            ->willReturn(['privateKey' => 'mock-private-key']);

        $this->mockJwsSigner
            ->expects($this->any())
            ->method('sign')
            ->willReturn('signed.jws.token');

        // Assert deprecation is triggered
        set_error_handler(function (int $errno, string $errstr): bool {
            $this->assertEquals(E_USER_DEPRECATED, $errno);
            $this->assertStringContainsString('passwordPri', $errstr);
            $this->assertStringContainsString('privateKeyPassword', $errstr);
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
            ->expects($this->once())
            ->method('validate');

        $this->mockLoader
            ->expects($this->once())
            ->method('loadCertificate')
            ->with('12345678901234', 'newpassword')
            ->willReturn(['privateKey' => 'mock-private-key']);

        $this->mockJwsSigner
            ->expects($this->once())
            ->method('sign')
            ->willReturn('signed.jws.token');

        // Act - no deprecation should trigger since privateKeyPassword is present
        $response = $this->signer->sign($request);

        // Assert
        $this->assertTrue($response['success']);
    }

    public function testConstructorWithDefaultDependencies(): void
    {
        // Act - should not throw
        $signer = new DteSigner();

        // Assert
        $this->assertInstanceOf(DteSigner::class, $signer);
    }

    public function testConstructorWithCustomDirectory(): void
    {
        // Act - should not throw
        $signer = new DteSigner('/custom/certificates/path');

        // Assert
        $this->assertInstanceOf(DteSigner::class, $signer);
    }
}
