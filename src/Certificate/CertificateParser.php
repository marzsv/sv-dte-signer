<?php

declare(strict_types=1);

namespace DteSigner\Certificate;

use DOMDocument;
use DteSigner\Exceptions\CertificateException;

/**
 * Parses XML certificate files to extract certificate data
 */
class CertificateParser
{
    /**
     * Parse XML certificate content and extract required data
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

        return [
            'activo' => $this->extractValue($document, 'activo'),
            'verificado' => $this->extractValue($document, 'verificado'),
            'privateKey' => $this->extractValue($document, 'privateKey'),
            'passwordHash' => $this->extractValue($document, 'passwordHash')
        ];
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