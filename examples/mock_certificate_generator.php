<?php

declare(strict_types=1);

/**
 * Mock Certificate Generator
 * 
 * This script generates mock certificates for testing purposes.
 * In production, certificates would be provided by the Ministry of Finance.
 */

require_once __DIR__ . '/../vendor/autoload.php';

class MockCertificateGenerator
{
    private const TEST_NIT = '12345678901234';
    private const TEST_PASSWORD = 'testpassword';
    private const CERTIFICATE_DIR = __DIR__ . '/../uploads';

    public function generate(): void
    {
        $this->ensureCertificateDirectory();
        $this->generateRsaKeyPair();
        $this->createMockCertificate();
        
        echo "Mock certificate generated successfully!\n";
        echo "NIT: " . self::TEST_NIT . "\n";
        echo "Password: " . self::TEST_PASSWORD . "\n";
        echo "Certificate file: " . $this->getCertificatePath() . "\n";
    }

    private function ensureCertificateDirectory(): void
    {
        if (!is_dir(self::CERTIFICATE_DIR)) {
            mkdir(self::CERTIFICATE_DIR, 0755, true);
        }
    }

    private function generateRsaKeyPair(): array
    {
        $config = [
            'digest_alg' => 'sha512',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $resource = openssl_pkey_new($config);
        
        if (!$resource) {
            throw new RuntimeException('Failed to generate RSA key pair');
        }

        openssl_pkey_export($resource, $privateKey);
        
        return [
            'private' => $privateKey,
            'resource' => $resource
        ];
    }

    private function createMockCertificate(): void
    {
        $keyPair = $this->generateRsaKeyPair();
        $passwordHash = hash('sha512', self::TEST_PASSWORD);

        $xmlContent = $this->buildCertificateXml([
            'activo' => 'true',
            'verificado' => 'true',
            'privateKey' => $keyPair['private'],
            'passwordHash' => $passwordHash
        ]);

        file_put_contents($this->getCertificatePath(), $xmlContent);
    }

    private function buildCertificateXml(array $data): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<certificate>
    <activo>{$data['activo']}</activo>
    <verificado>{$data['verificado']}</verificado>
    <passwordHash>{$data['passwordHash']}</passwordHash>
    <privateKey><![CDATA[{$data['privateKey']}]]></privateKey>
</certificate>
XML;
    }

    private function getCertificatePath(): string
    {
        return self::CERTIFICATE_DIR . '/' . self::TEST_NIT . '.crt';
    }
}

// Generate certificate if script is run directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $generator = new MockCertificateGenerator();
    $generator->generate();
}