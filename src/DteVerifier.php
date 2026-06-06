<?php

declare(strict_types=1);

namespace Marzsv\DteSigner;

use Marzsv\DteSigner\Certificate\CertificateLoader;
use Marzsv\DteSigner\Contracts\CertificateLoaderInterface;
use Marzsv\DteSigner\Contracts\JwsVerifierInterface;
use Marzsv\DteSigner\Contracts\RateLimiterInterface;
use Marzsv\DteSigner\Exceptions\DteSignerException;
use Marzsv\DteSigner\Exceptions\VerificationException;
use Marzsv\DteSigner\Security\NullRateLimiter;
use Marzsv\DteSigner\Signing\JwsVerifier;
use Marzsv\DteSigner\Utils\ResponseBuilder;
use Marzsv\DteSigner\Validators\NitValidator;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Main DTE Verifier class for verifying and extracting content from signed DTEs
 *
 * This class provides functionality to verify JWS signatures of Documentos Tributarios
 * Electrónicos (DTE) and extract the original JSON content.
 */
class DteVerifier
{
    private CertificateLoaderInterface $certificateLoader;
    private JwsVerifierInterface $jwsVerifier;
    private LoggerInterface $logger;
    private RateLimiterInterface $rateLimiter;

    /**
     * Initialize DTE Verifier with certificate directory
     */
    public function __construct(
        string $certificateDirectory = Config::DEFAULT_CERTIFICATE_DIRECTORY,
        ?CertificateLoaderInterface $certificateLoader = null,
        ?JwsVerifierInterface $jwsVerifier = null,
        ?LoggerInterface $logger = null,
        ?RateLimiterInterface $rateLimiter = null
    ) {
        $this->certificateLoader = $certificateLoader ?? new CertificateLoader($certificateDirectory);
        $this->jwsVerifier = $jwsVerifier ?? new JwsVerifier();
        $this->logger = $logger ?? new NullLogger();
        $this->rateLimiter = $rateLimiter ?? new NullRateLimiter();
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

            if (!$this->rateLimiter->isAllowed($nit)) {
                throw new VerificationException(
                    'Too many verification attempts',
                    ['Rate limit exceeded']
                );
            }

            $nitErrors = NitValidator::validate($nit);
            if (!empty($nitErrors)) {
                throw new VerificationException(
                    'Invalid NIT format',
                    $nitErrors
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

            $payload = $verificationResult['payload'];
            if (!is_array($payload)) {
                throw new VerificationException(
                    'Invalid payload type from verification',
                    ['Payload type error']
                );
            }

            $this->rateLimiter->reset($nit);

            return ResponseBuilder::verificationSuccess(
                $payload,
                'DTE signature verified successfully'
            );

        } catch (DteSignerException $e) {
            if (!empty($nit) && NitValidator::isValid($nit)) {
                $this->rateLimiter->recordAttempt($nit);
            }

            return ResponseBuilder::error($e);
        } catch (\Exception $e) {
            if (!empty($nit) && NitValidator::isValid($nit)) {
                $this->rateLimiter->recordAttempt($nit);
            }

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