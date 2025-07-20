<?php

declare(strict_types=1);

namespace DteSigner;

use DteSigner\Certificate\CertificateLoader;
use DteSigner\Exceptions\DteSignerException;
use DteSigner\Exceptions\ValidationException;
use DteSigner\Signing\JwsSigner;
use DteSigner\Utils\ResponseBuilder;
use DteSigner\Validators\RequestValidator;

/**
 * Main DTE Signer class for signing electronic documents
 * 
 * This class provides functionality to sign Documentos Tributarios Electrónicos (DTE)
 * for El Salvador using JWS RS512 digital signatures.
 */
class DteSigner
{
    private const DEFAULT_CERTIFICATE_DIRECTORY = 'certificates';

    private RequestValidator $requestValidator;
    private CertificateLoader $certificateLoader;
    private JwsSigner $jwsSigner;

    /**
     * Initialize DTE Signer with certificate directory
     */
    public function __construct(
        string $certificateDirectory = self::DEFAULT_CERTIFICATE_DIRECTORY,
        ?RequestValidator $requestValidator = null,
        ?CertificateLoader $certificateLoader = null,
        ?JwsSigner $jwsSigner = null
    ) {
        $this->requestValidator = $requestValidator ?? new RequestValidator();
        $this->certificateLoader = $certificateLoader ?? new CertificateLoader($certificateDirectory);
        $this->jwsSigner = $jwsSigner ?? new JwsSigner();
    }

    /**
     * Sign a DTE document
     * 
     * @param array<string, mixed>|string $input Either an array with signing data or a file path to JSON
     * @return array<string, mixed> Response array with success/error information
     */
    public function sign(array|string $input): array
    {
        try {
            $requestData = $this->parseInput($input);
            $this->requestValidator->validate($requestData);

            $certificateData = $this->certificateLoader->loadCertificate(
                $requestData['nit'],
                $requestData['passwordPri']
            );

            $signedJws = $this->jwsSigner->sign(
                $requestData['dteJson'],
                $certificateData['privateKey']
            );

            return ResponseBuilder::success($signedJws);

        } catch (DteSignerException $e) {
            return ResponseBuilder::error($e);
        } catch (\Exception $e) {
            return ResponseBuilder::genericError(
                'Unexpected error: ' . $e->getMessage(),
                'COD_500'
            );
        } finally {
            $this->clearSensitiveData($requestData ?? []);
        }
    }

    /**
     * Parse input data from array or file path
     * 
     * @param array<string, mixed>|string $input
     * @return array<string, mixed>
     * @throws ValidationException
     */
    private function parseInput(array|string $input): array
    {
        if (is_string($input)) {
            return $this->loadFromFile($input);
        }

        return $input;
    }

    /**
     * Load request data from JSON file
     * 
     * @return array<string, mixed>
     * @throws ValidationException
     */
    private function loadFromFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new ValidationException(
                "Request file not found: {$filePath}",
                ['File does not exist']
            );
        }

        $content = file_get_contents($filePath);
        
        if ($content === false) {
            throw new ValidationException(
                "Could not read request file: {$filePath}",
                ['File read error']
            );
        }

        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ValidationException(
                'Invalid JSON in request file: ' . json_last_error_msg(),
                ['JSON parsing error']
            );
        }

        return $data;
    }

    /**
     * Clear sensitive data from memory for security
     * 
     * @param array<string, mixed> $data
     */
    private function clearSensitiveData(array $data): void
    {
        if (isset($data['passwordPri'])) {
            unset($data['passwordPri']);
        }
        
        if (isset($data['passwordPub'])) {
            unset($data['passwordPub']);
        }
    }
}