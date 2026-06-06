<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Certificate;

use Marzsv\DteSigner\Cache\NullCache;
use Marzsv\DteSigner\Contracts\CacheInterface;
use Marzsv\DteSigner\Contracts\CertificateLoaderInterface;
use Marzsv\DteSigner\Contracts\KeyFormatterInterface;
use Marzsv\DteSigner\Exceptions\CertificateException;
use Marzsv\DteSigner\Exceptions\DteSignerException;
use Marzsv\DteSigner\Utils\Formatters\CompositeKeyFormatter;
use Marzsv\DteSigner\Utils\KeyFormatter;
use Marzsv\DteSigner\Validators\CertificateValidator;

/**
 * Loads and validates XML certificates from the file system
 */
class CertificateLoader implements CertificateLoaderInterface
{
    private const CERTIFICATE_EXTENSION = '.crt';

    private string $certificateDirectory;
    private CertificateParser $parser;
    private CertificateValidator $validator;
    private KeyFormatterInterface $keyFormatter;
    private CacheInterface $cache;

    public function __construct(
        string $certificateDirectory,
        ?CertificateParser $parser = null,
        ?CertificateValidator $validator = null,
        ?KeyFormatterInterface $keyFormatter = null,
        ?CacheInterface $cache = null
    ) {
        $this->certificateDirectory = rtrim($certificateDirectory, '/');
        $this->parser = $parser ?? new CertificateParser();
        $this->validator = $validator ?? new CertificateValidator();
        $this->keyFormatter = $keyFormatter ?? new CompositeKeyFormatter();
        $this->cache = $cache ?? new NullCache();
    }

    /**
     * Load and validate certificate for the given NIT
     *
     * @return array<string, mixed>
     * @throws CertificateException
     */
    public function loadCertificate(string $nit, string $password): array
    {
        $cacheKey = "cert:{$nit}";

        $cached = $this->cache->get($cacheKey);
        if (is_array($cached)) {
            $this->validator->validate($cached, $password);
            return $cached;
        }

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

        $this->cache->put($cacheKey, $certificateData);

        return $certificateData;
    }

    /**
     * Get public key for verification from certificate
     *
     * @throws CertificateException
     */
    public function getPublicKey(string $nit): string
    {
        $publicKeyCacheKey = "pubkey:{$nit}";

        $cachedPublicKey = $this->cache->get($publicKeyCacheKey);
        if (is_string($cachedPublicKey)) {
            return $cachedPublicKey;
        }

        $certificateCacheKey = "cert:{$nit}";
        $cachedCert = $this->cache->get($certificateCacheKey);

        if (is_array($cachedCert)) {
            $certificateData = $cachedCert;
        } else {
            $certificateFile = $this->buildCertificatePath($nit);

            if (!file_exists($certificateFile)) {
                throw CertificateException::certificateNotFound($nit);
            }

            $xmlContent = file_get_contents($certificateFile);

            if ($xmlContent === false) {
                throw CertificateException::invalidCertificate('Could not read certificate file');
            }

            $certificateData = $this->parser->parse($xmlContent);
            $this->cache->put($certificateCacheKey, $certificateData);
        }

        if (empty($certificateData['privateKey']) || !is_string($certificateData['privateKey'])) {
            throw CertificateException::invalidCertificate('No private key found in certificate');
        }

        $publicKey = $this->extractPublicKeyFromPrivateKey($certificateData['privateKey']);
        $this->cache->put($publicKeyCacheKey, $publicKey);

        return $publicKey;
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
            $processedKey = $this->keyFormatter->toPem($privateKey);

            // Get the private key resource
            $privateKeyResource = openssl_pkey_get_private($processedKey);

            if ($privateKeyResource === false) {
                $error = openssl_error_string();
                throw CertificateException::invalidCertificate(
                    'Cannot load private key' . ($error ? ': ' . $error : '')
                );
            }

            // Extract public key details and export as PEM
            $publicKeyDetails = openssl_pkey_get_details($privateKeyResource);

            if ($publicKeyDetails === false) {
                $error = openssl_error_string();
                throw CertificateException::invalidCertificate(
                    'Cannot get public key details' . ($error ? ': ' . $error : '')
                );
            }

            if (!isset($publicKeyDetails['key'])) {
                throw CertificateException::invalidCertificate(
                    'Public key not found in key details'
                );
            }

            return $publicKeyDetails['key'];

        } catch (CertificateException $e) {
            throw $e;
        } catch (DteSignerException $e) {
            throw CertificateException::invalidCertificate($e->getMessage());
        } catch (\Exception $e) {
            throw CertificateException::invalidCertificate(
                'Failed to extract public key: ' . $e->getMessage()
            );
        }
    }

    /**
     * Build the full path to the certificate file
     */
    private function buildCertificatePath(string $nit): string
    {
        return $this->certificateDirectory . '/' . $nit . self::CERTIFICATE_EXTENSION;
    }
}