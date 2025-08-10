<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Signing;

use Marzsv\DteSigner\Exceptions\VerificationException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Verifies JWS signatures and extracts payloads using RS512 algorithm
 */
class JwsVerifier
{
    private const ALGORITHM = 'RS512';
    private const JWT_PARTS_COUNT = 3;

    /**
     * Verify JWS signature and return payload if valid
     * 
     * @param string $jwsToken The JWS token to verify
     * @param string $publicKey The public key to verify against
     * @return array<string, mixed> Array with 'valid' boolean and 'payload' data
     * @throws VerificationException
     */
    public function verifySignature(string $jwsToken, string $publicKey): array
    {
        try {
            if (empty($jwsToken)) {
                throw new VerificationException(
                    'JWS token cannot be empty',
                    ['JWS token is required']
                );
            }

            if (empty($publicKey)) {
                throw new VerificationException(
                    'Public key cannot be empty',
                    ['Public key is required for verification']
                );
            }

            $this->validateJwsFormat($jwsToken);

            $key = new Key($publicKey, self::ALGORITHM);
            $decoded = JWT::decode($jwsToken, $key);

            // Firebase JWT returns object, convert to array
            $encodedPayload = json_encode($decoded);
            if ($encodedPayload === false) {
                throw new VerificationException(
                    'Failed to encode JWT payload',
                    ['Payload encode error']
                );
            }
            
            $payload = json_decode($encodedPayload, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new VerificationException(
                    'Failed to decode JWT payload: ' . json_last_error_msg(),
                    ['Payload decode error']
                );
            }

            return [
                'valid' => true,
                'payload' => $payload
            ];

        } catch (\Firebase\JWT\ExpiredException $e) {
            return [
                'valid' => false,
                'payload' => null,
                'error' => 'Token has expired'
            ];
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            return [
                'valid' => false,
                'payload' => null,
                'error' => 'Invalid signature'
            ];
        } catch (\Firebase\JWT\BeforeValidException $e) {
            return [
                'valid' => false,
                'payload' => null,
                'error' => 'Token is not yet valid'
            ];
        } catch (\DomainException $e) {
            throw new VerificationException(
                'Invalid JWT format: ' . $e->getMessage(),
                ['JWT format error']
            );
        } catch (\Exception $e) {
            throw new VerificationException(
                'Signature verification failed: ' . $e->getMessage(),
                ['Verification error']
            );
        }
    }

    /**
     * Extract payload from JWS token without verifying signature
     * 
     * WARNING: This method does not verify the signature. The extracted
     * data should not be trusted without separate signature verification.
     * 
     * @param string $jwsToken The JWS token to extract payload from
     * @return array<string, mixed> The decoded payload data
     * @throws VerificationException
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

            $this->validateJwsFormat($jwsToken);

            $parts = explode('.', $jwsToken);
            $payloadPart = $parts[1];

            // Add padding if needed for base64url decode
            $payloadPart = $this->addBase64Padding($payloadPart);
            
            $decodedPayload = base64_decode(strtr($payloadPart, '-_', '+/'), true);
            
            if ($decodedPayload === false) {
                throw new VerificationException(
                    'Failed to decode JWT payload',
                    ['Base64 decode error']
                );
            }

            $payload = json_decode($decodedPayload, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new VerificationException(
                    'Invalid JSON in JWT payload: ' . json_last_error_msg(),
                    ['JSON decode error']
                );
            }

            return $payload;

        } catch (\Exception $e) {
            if ($e instanceof VerificationException) {
                throw $e;
            }
            
            throw new VerificationException(
                'Failed to extract payload: ' . $e->getMessage(),
                ['Payload extraction error']
            );
        }
    }

    /**
     * Validate JWS token format
     * 
     * @throws VerificationException
     */
    private function validateJwsFormat(string $jwsToken): void
    {
        $parts = explode('.', $jwsToken);
        
        if (count($parts) !== self::JWT_PARTS_COUNT) {
            throw new VerificationException(
                'Invalid JWT format: expected 3 parts separated by dots',
                ['JWT format error']
            );
        }

        foreach ($parts as $index => $part) {
            if (empty($part)) {
                $partNames = ['header', 'payload', 'signature'];
                throw new VerificationException(
                    'Invalid JWT format: empty ' . $partNames[$index],
                    ['JWT format error']
                );
            }
        }
    }

    /**
     * Add padding to base64 string if needed
     */
    private function addBase64Padding(string $base64): string
    {
        $remainder = strlen($base64) % 4;
        
        if ($remainder !== 0) {
            $paddingLength = 4 - $remainder;
            $base64 .= str_repeat('=', $paddingLength);
        }

        return $base64;
    }
}