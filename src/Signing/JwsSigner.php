<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Signing;

use Marzsv\DteSigner\Exceptions\DteSignerException;
use Marzsv\DteSigner\Utils\KeyFormatter;
use Firebase\JWT\JWT;

/**
 * Creates JWS signatures using RS512 algorithm
 */
class JwsSigner
{
    private const ALGORITHM = 'RS512';

    /**
     * Sign DTE JSON data and return JWS compact serialization
     *
     * @param array<string, mixed> $dteJson
     * @throws DteSignerException
     */
    public function sign(array $dteJson, string $privateKey, ?string $password = null): string
    {
        try {
            if (empty($privateKey)) {
                throw new DteSignerException(
                    'Private key cannot be empty',
                    'COD_815'
                );
            }

            if (empty($dteJson)) {
                throw new DteSignerException(
                    'DTE JSON cannot be empty',
                    'COD_816'
                );
            }

            // Process the private key (handle both PEM and base64 formats)
            $processedKey = KeyFormatter::toPemDecrypted($privateKey, $password);

            $header = ['alg' => self::ALGORITHM, 'typ' => 'JWT'];

            return JWT::encode($dteJson, $processedKey, self::ALGORITHM, null, $header);

        } catch (DteSignerException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new DteSignerException(
                'Failed to sign DTE: ' . $e->getMessage(),
                'COD_815'
            );
        }
    }
}