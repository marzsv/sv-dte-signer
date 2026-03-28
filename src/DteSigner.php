<?php

declare(strict_types=1);

namespace Marzsv\DteSigner;

use Marzsv\DteSigner\Certificate\CertificateLoader;
use Marzsv\DteSigner\Exceptions\DteSignerException;
use Marzsv\DteSigner\Exceptions\ValidationException;
use Marzsv\DteSigner\Signing\JwsSigner;
use Marzsv\DteSigner\Utils\ResponseBuilder;
use Marzsv\DteSigner\Validators\RequestValidator;

/**
 * Main DTE Signer class for signing electronic documents
 *
 * This class provides functionality to sign Documentos Tributarios Electrónicos (DTE)
 * for El Salvador using JWS RS512 digital signatures.
 */
class DteSigner
{
    private RequestValidator $requestValidator;
    private CertificateLoader $certificateLoader;
    private JwsSigner $jwsSigner;

    /**
     * Initialize DTE Signer with certificate directory
     */
    public function __construct(
        string $certificateDirectory = Config::DEFAULT_CERTIFICATE_DIRECTORY,
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
        $requestData = [];
        try {
            $requestData = $this->parseInput($input);
            $requestData = $this->normalizeFieldNames($requestData);
            $this->requestValidator->validate($requestData);

            $certificateData = $this->certificateLoader->loadCertificate(
                $requestData['nit'],
                $requestData['privateKeyPassword']
            );

            $signedJws = $this->jwsSigner->sign(
                $requestData['dteJson'],
                $certificateData['privateKey'],
                $requestData['privateKeyPassword']
            );

            return ResponseBuilder::success($signedJws, 'DTE signed successfully', [
                'notBefore' => $certificateData['notBefore'] ?? null,
                'notAfter' => $certificateData['notAfter'] ?? null,
            ]);

        } catch (DteSignerException $e) {
            return ResponseBuilder::error($e);
        } catch (\Exception $e) {
            return ResponseBuilder::genericError(
                'Unexpected error: ' . $e->getMessage(),
                'COD_500'
            );
        } finally {
            $this->clearSensitiveData($requestData);
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
     * Normalize deprecated field names to current names
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeFieldNames(array $data): array
    {
        if (isset($data['passwordPri']) && !isset($data['privateKeyPassword'])) {
            @trigger_error(
                'The field "passwordPri" is deprecated since v1.4.0, use "privateKeyPassword" instead.',
                E_USER_DEPRECATED
            );
            $data['privateKeyPassword'] = $data['passwordPri'];
            unset($data['passwordPri']);
        }

        return $data;
    }

    /**
     * Clear sensitive data from memory for security
     *
     * Overwrites sensitive values before unsetting to minimize the time
     * passwords remain in memory.
     *
     * @param array<string, mixed> $data Reference to the data array
     */
    private function clearSensitiveData(array &$data): void
    {
        $sensitiveFields = ['privateKeyPassword', 'passwordPri'];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = str_repeat("\0", strlen((string) $data[$field]));
                unset($data[$field]);
            }
        }
    }
}