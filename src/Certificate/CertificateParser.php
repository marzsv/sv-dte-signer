<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Certificate;

use DOMDocument;
use DOMXPath;
use Marzsv\DteSigner\Exceptions\CertificateException;

/**
 * Parses XML certificate files from Ministerio de Hacienda (MH) format
 */
class CertificateParser
{
    /**
     * Parse MH certificate XML content and extract required data
     * 
     * @return array<string, mixed>
     * @throws CertificateException
     */
    public function parse(string $xmlContent): array
    {
        $document = new DOMDocument();
        
        if (!$document->loadXML($xmlContent)) {
            throw CertificateException::invalidCertificate('Invalid XML format');
        }

        // Verify it's an MH certificate
        if ($document->getElementsByTagName('CertificadoMH')->length === 0) {
            throw CertificateException::invalidCertificate('Certificate must be in MH (Ministerio de Hacienda) format');
        }

        return $this->parseMhCertificate($document);
    }

    /**
     * Parse MH (Ministerio de Hacienda) format certificate
     * 
     * @return array<string, mixed>
     */
    private function parseMhCertificate(DOMDocument $document): array
    {
        $xpath = new DOMXPath($document);
        
        // Extract activo (active status)
        $activo = $this->extractValue($document, 'activo');
        $activoBool = $activo === 'true' || $activo === '1';
        
        // Extract verificado (verified status) 
        $verificado = $this->extractValue($document, 'verificado');
        $verificadoBool = !empty($verificado) && $verificado !== 'false' && $verificado !== '0';
        
        // Extract private key from MH structure: <privateKey><encodied>...</encodied></privateKey>
        $privateKeyElements = $xpath->query('//privateKey/encodied');
        $privateKey = null;
        if ($privateKeyElements && $privateKeyElements->length > 0) {
            $privateKey = $privateKeyElements->item(0)->nodeValue;
            // Clean the private key (remove newlines and spaces)
            $privateKey = preg_replace('/\s+/', '', $privateKey);
        }

        // For MH certificates, we don't store password hashes in the XML
        // Instead, we generate a simple placeholder hash for compatibility
        $passwordHash = $this->generatePlaceholderHash($document);

        if (!$privateKey) {
            throw CertificateException::invalidCertificate('Private key not found in MH certificate');
        }

        return [
            'activo' => $activoBool ? 'true' : 'false',
            'verificado' => $verificadoBool ? 'true' : 'false', 
            'privateKey' => $privateKey,
            'passwordHash' => $passwordHash
        ];
    }

    /**
     * Generate a placeholder password hash for MH certificates
     * MH certificates don't store password hashes, so we create a placeholder
     */
    private function generatePlaceholderHash(DOMDocument $document): string
    {
        // Use NIT and private key clave as basis for a consistent hash
        $nit = $this->extractValue($document, 'nit') ?? '';
        $privateKeyClave = $this->extractValueFromPath($document, '//privateKey/clave') ?? '';
        
        // Generate a consistent SHA256 hash (64 chars) to identify this as an MH certificate
        return hash('sha256', $nit . $privateKeyClave);
    }

    /**
     * Extract value from XML document by XPath
     */
    private function extractValueFromPath(DOMDocument $document, string $xpath): ?string
    {
        $xpathObj = new DOMXPath($document);
        $elements = $xpathObj->query($xpath);
        
        if (!$elements || $elements->length === 0) {
            return null;
        }

        return $elements->item(0)?->nodeValue;
    }

    /**
     * Extract value from XML document by tag name
     */
    private function extractValue(DOMDocument $document, string $tagName): ?string
    {
        $elements = $document->getElementsByTagName($tagName);
        
        if ($elements->length === 0) {
            return null;
        }

        return $elements->item(0)?->nodeValue;
    }
}