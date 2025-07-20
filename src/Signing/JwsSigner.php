<?php

declare(strict_types=1);

namespace DteSigner\Signing;

use DteSigner\Exceptions\DteSignerException;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Rsa\Sha512;
use Lcobucci\JWT\Signer\Key\InMemory;

/**
 * Creates JWS signatures using RS512 algorithm
 */
class JwsSigner
{
    private const ALGORITHM = 'RS512';
    private const JSON_ENCODE_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    /**
     * Sign DTE JSON data and return JWS compact serialization
     * 
     * @param array<string, mixed> $dteJson
     * @throws DteSignerException
     */
    public function sign(array $dteJson, string $privateKey): string
    {
        try {
            $payload = $this->preparePayload($dteJson);
            
            if (empty($privateKey)) {
                throw new DteSignerException(
                    'Private key cannot be empty',
                    'COD_815'
                );
            }
            
            $key = InMemory::plainText($privateKey);
            $signer = new Sha512();
            
            $configuration = Configuration::forAsymmetricSigner(
                $signer,
                $key,
                InMemory::plainText('dummy') // Public key not needed for signing
            );
            
            $token = $configuration->builder()
                ->withHeader('alg', self::ALGORITHM)
                ->withClaim('dte', $payload)
                ->getToken($signer, $key);

            return $token->toString();

        } catch (\Exception $e) {
            throw new DteSignerException(
                'Failed to sign DTE: ' . $e->getMessage(),
                'COD_815'
            );
        }
    }

    /**
     * Prepare DTE JSON as UTF-8 pretty-printed string
     * 
     * @param array<string, mixed> $dteJson
     */
    private function preparePayload(array $dteJson): string
    {
        $jsonString = json_encode($dteJson, self::JSON_ENCODE_FLAGS);
        
        if ($jsonString === false) {
            throw new DteSignerException(
                'Failed to encode DTE JSON',
                'COD_816'
            );
        }

        return $jsonString;
    }
}