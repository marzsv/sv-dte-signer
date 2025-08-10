<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Certificate;

use Marzsv\DteSigner\Exceptions\CertificateException;
use Marzsv\DteSigner\Validators\CertificateValidator;

/**
 * Loads and validates XML certificates from the file system
 */
class CertificateLoader
{
    private const CERTIFICATE_EXTENSION = '.crt';

    private string $certificateDirectory;
    private CertificateParser $parser;
    private CertificateValidator $validator;

    public function __construct(
        string $certificateDirectory,
        ?CertificateParser $parser = null,
        ?CertificateValidator $validator = null
    ) {
        $this->certificateDirectory = rtrim($certificateDirectory, '/');
        $this->parser = $parser ?? new CertificateParser();
        $this->validator = $validator ?? new CertificateValidator();
    }

    /**
     * Load and validate certificate for the given NIT
     * 
     * @return array<string, mixed>
     * @throws CertificateException
     */
    public function loadCertificate(string $nit, string $password): array
    {
        $certificateFile = $this->buildCertificatePath($nit);
        
        if (!file_exists($certificateFile)) {
            throw CertificateException::certificateNotFound($nit);
        }

        $xmlContent = file_get_contents($certificateFile);
        
        if ($xmlContent === false) {
            throw CertificateException::invalidCertificate('Could not read certificate file');
        }

        $certificateData = $this->parser->parse($xmlContent);
        $this->validator->validate($certificateData, $password);

        return $certificateData;
    }

    /**
     * Get public key for verification from certificate
     * 
     * @throws CertificateException
     */
    public function getPublicKey(string $nit): string
    {
        $certificateFile = $this->buildCertificatePath($nit);
        
        if (!file_exists($certificateFile)) {
            throw CertificateException::certificateNotFound($nit);
        }

        $xmlContent = file_get_contents($certificateFile);
        
        if ($xmlContent === false) {
            throw CertificateException::invalidCertificate('Could not read certificate file');
        }

        $certificateData = $this->parser->parse($xmlContent);
        
        if (empty($certificateData['privateKey'])) {
            throw CertificateException::invalidCertificate('No private key found in certificate');
        }

        return $this->extractPublicKeyFromPrivateKey($certificateData['privateKey']);
    }

    /**
     * Extract public key from private key
     * 
     * @throws CertificateException
     */
    private function extractPublicKeyFromPrivateKey(string $privateKey): string
    {
        try {
            // Process the private key (handle both PEM and base64 formats)
            $processedKey = $this->processPrivateKeyForPublic($privateKey);
            
            // Get the private key resource
            $privateKeyResource = openssl_pkey_get_private($processedKey);
            
            if ($privateKeyResource === false) {
                throw CertificateException::invalidCertificate(
                    'Cannot load private key: ' . openssl_error_string()
                );
            }

            // Extract public key details and export as PEM
            $publicKeyDetails = openssl_pkey_get_details($privateKeyResource);
            
            if ($publicKeyDetails === false) {
                throw CertificateException::invalidCertificate(
                    'Cannot get public key details: ' . openssl_error_string()
                );
            }

            if (!isset($publicKeyDetails['key'])) {
                throw CertificateException::invalidCertificate(
                    'Public key not found in key details'
                );
            }

            return $publicKeyDetails['key'];

        } catch (\Exception $e) {
            if ($e instanceof CertificateException) {
                throw $e;
            }
            
            throw CertificateException::invalidCertificate(
                'Failed to extract public key: ' . $e->getMessage()
            );
        }
    }

    /**
     * Process private key to ensure it's in the correct format for OpenSSL
     * 
     * @throws CertificateException
     */
    private function processPrivateKeyForPublic(string $privateKey): string
    {
        // If it already looks like a PEM key, use it directly
        if (str_contains($privateKey, '-----BEGIN') && str_contains($privateKey, '-----END')) {
            return $privateKey;
        }

        // If it's base64 encoded (from MH certificates), decode it
        $decodedKey = base64_decode($privateKey, true);
        if ($decodedKey === false) {
            throw CertificateException::invalidCertificate('Invalid base64 private key format');
        }

        // Convert DER to PEM format
        $pemKey = "-----BEGIN PRIVATE KEY-----\n" . 
                  chunk_split(base64_encode($decodedKey), 64, "\n") . 
                  "-----END PRIVATE KEY-----";

        return $pemKey;
    }

    /**
     * Build the full path to the certificate file
     */
    private function buildCertificatePath(string $nit): string
    {
        return $this->certificateDirectory . '/' . $nit . self::CERTIFICATE_EXTENSION;
    }
}