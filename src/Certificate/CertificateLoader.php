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
     * Build the full path to the certificate file
     */
    private function buildCertificatePath(string $nit): string
    {
        return $this->certificateDirectory . '/' . $nit . self::CERTIFICATE_EXTENSION;
    }
}