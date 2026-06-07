<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Tests\Unit;

use Marzsv\DteSigner\Exceptions\DteSignerException;
use Marzsv\DteSigner\Utils\KeyFormatter;
use PHPUnit\Framework\TestCase;

class KeyFormatterTest extends TestCase
{
    private string $testPemKey = '';

    protected function setUp(): void
    {
        // Generate a test RSA key
        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $resource = openssl_pkey_new($config);
        if ($resource === false) {
            throw new \RuntimeException('Failed to generate test RSA key');
        }
        $pemKey = '';
        if (!openssl_pkey_export($resource, $pemKey) || !is_string($pemKey)) {
            throw new \RuntimeException('Failed to export test RSA key');
        }
        $this->testPemKey = $pemKey;
    }

    public function testIsPemFormatWithValidPem(): void
    {
        self::assertTrue(KeyFormatter::isPemFormat($this->testPemKey));
    }

    public function testIsPemFormatWithBase64String(): void
    {
        $base64Key = base64_encode('some binary data');
        self::assertFalse(KeyFormatter::isPemFormat($base64Key));
    }

    public function testIsPemFormatWithRandomString(): void
    {
        self::assertFalse(KeyFormatter::isPemFormat('random string'));
    }

    public function testToPemWithAlreadyPemKey(): void
    {
        $result = KeyFormatter::toPem($this->testPemKey);
        self::assertEquals($this->testPemKey, $result);
    }

    public function testToPemWithBase64DerKey(): void
    {
        // Extract the base64 content from the PEM key
        $pemLines = explode("\n", $this->testPemKey);
        $base64Content = '';
        foreach ($pemLines as $line) {
            if (!str_contains($line, '-----')) {
                $base64Content .= $line;
            }
        }

        // Decode to DER format
        $derKey = base64_decode($base64Content, true);
        self::assertNotFalse($derKey);
        // Re-encode to base64 (simulating MH format)
        $base64DerKey = base64_encode($derKey);

        $result = KeyFormatter::toPem($base64DerKey);

        self::assertStringContainsString('-----BEGIN PRIVATE KEY-----', $result);
        self::assertStringContainsString('-----END PRIVATE KEY-----', $result);
    }

    public function testToPemWithInvalidBase64ThrowsException(): void
    {
        $this->expectException(DteSignerException::class);
        $this->expectExceptionMessage('Invalid base64 private key format');

        // Invalid base64 string (contains invalid characters)
        KeyFormatter::toPem('not!valid@base64#string');
    }

    public function testToPemDecryptedWithNullPasswordReturnsUnencryptedKey(): void
    {
        $result = KeyFormatter::toPemDecrypted($this->testPemKey, null);

        self::assertStringContainsString('-----BEGIN', $result);
        self::assertStringContainsString('-----END', $result);
    }

    public function testToPemDecryptedWithPasswordOnUnencryptedKey(): void
    {
        // Unencrypted key should work even if password is provided
        // (OpenSSL will just use the key without decryption)
        $result = KeyFormatter::toPemDecrypted($this->testPemKey, 'unused_password');

        self::assertStringContainsString('-----BEGIN', $result);
        self::assertStringContainsString('-----END', $result);
    }

    public function testToPemDecryptedWithInvalidKeyThrowsException(): void
    {
        $this->expectException(DteSignerException::class);

        // Valid base64 but not a valid key
        $fakeKey = base64_encode('not a real key');
        KeyFormatter::toPemDecrypted($fakeKey, 'password');
    }
}
