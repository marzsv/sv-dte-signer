<?php

declare(strict_types=1);

namespace Marzsv\DteSigner;

use Marzsv\DteSigner\Certificate\CertificateLoader;
use Marzsv\DteSigner\Exceptions\DteSignerException;
use Marzsv\DteSigner\Exceptions\VerificationException;
use Marzsv\DteSigner\Signing\JwsVerifier;
use Marzsv\DteSigner\Utils\ResponseBuilder;

/**
 * Main DTE Verifier class for verifying and extracting content from signed DTEs
 * 
 * This class provides functionality to verify JWS signatures of Documentos Tributarios 
 * Electrónicos (DTE) and extract the original JSON content.
 */
class DteVerifier
{
    private const DEFAULT_CERTIFICATE_DIRECTORY = 'certificates';

    private CertificateLoader $certificateLoader;
    private JwsVerifier $jwsVerifier;

    /**
     * Initialize DTE Verifier with certificate directory
     */
    public function __construct(
        string $certificateDirectory = self::DEFAULT_CERTIFICATE_DIRECTORY,
        ?CertificateLoader $certificateLoader = null,
        ?JwsVerifier $jwsVerifier = null
    ) {
        $this->certificateLoader = $certificateLoader ?? new CertificateLoader($certificateDirectory);
        $this->jwsVerifier = $jwsVerifier ?? new JwsVerifier();
    }

    /**
     * Verify a signed DTE and extract the original content
     * 
     * @param string $jwsToken The JWS token to verify
     * @param string $nit The NIT of the certificate to verify against
     * @return array<string, mixed> Response array with success/error information
     */
    public function verify(string $jwsToken, string $nit): array
    {
        try {
            if (empty($jwsToken)) {
                throw new VerificationException(
                    'JWS token cannot be empty',
                    ['JWS token is required']
                );
            }

            if (empty($nit) || strlen($nit) !== 14) {
                throw new VerificationException(
                    'Invalid NIT format',
                    ['NIT must be exactly 14 characters long']
                );
            }

            $publicKey = $this->certificateLoader->getPublicKey($nit);
            
            $verificationResult = $this->jwsVerifier->verifySignature($jwsToken, $publicKey);
            
            if (!$verificationResult['valid']) {
                throw new VerificationException(
                    'Invalid JWS signature',
                    ['Signature verification failed']
                );
            }

            return ResponseBuilder::verificationSuccess(
                $verificationResult['payload'],
                'DTE signature verified successfully'
            );

        } catch (DteSignerException $e) {
            return ResponseBuilder::error($e);
        } catch (\Exception $e) {
            return ResponseBuilder::genericError(
                'Unexpected verification error: ' . $e->getMessage(),
                'COD_500'
            );
        }
    }

    /**
     * Extract payload from JWS token without signature verification
     * 
     * WARNING: This method does not verify the signature. Use only when
     * signature verification is not required or has been done elsewhere.
     * 
     * @param string $jwsToken The JWS token to extract payload from
     * @return array<string, mixed> Response array with success/error information
     */
    public function extractPayload(string $jwsToken): array
    {
        try {
            if (empty($jwsToken)) {
                throw new VerificationException(
                    'JWS token cannot be empty',
                    ['JWS token is required']
                );
            }

            $payload = $this->jwsVerifier->extractPayload($jwsToken);

            return ResponseBuilder::verificationSuccess(
                $payload,
                'DTE payload extracted successfully (signature not verified)'
            );

        } catch (DteSignerException $e) {
            return ResponseBuilder::error($e);
        } catch (\Exception $e) {
            return ResponseBuilder::genericError(
                'Unexpected extraction error: ' . $e->getMessage(),
                'COD_500'
            );
        }
    }
}