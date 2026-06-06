<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Utils\Formatters;

use Marzsv\DteSigner\Contracts\KeyFormatterInterface;
use Marzsv\DteSigner\Exceptions\DteSignerException;

/**
 * Composite key formatter that delegates to the first supporting formatter
 *
 * Tries each formatter in order until one supports the key format.
 */
class CompositeKeyFormatter implements KeyFormatterInterface
{
    /** @var array<KeyFormatterInterface> */
    private array $formatters;

    /**
     * @param array<KeyFormatterInterface>|null $formatters List of formatters to try in order
     */
    public function __construct(?array $formatters = null)
    {
        $this->formatters = $formatters ?? [
            new PemKeyFormatter(),
            new DerKeyFormatter(),
        ];
    }

    public function supports(string $key): bool
    {
        foreach ($this->formatters as $formatter) {
            if ($formatter->supports($key)) {
                return true;
            }
        }

        return false;
    }

    public function toPem(string $key): string
    {
        foreach ($this->formatters as $formatter) {
            if ($formatter->supports($key)) {
                return $formatter->toPem($key);
            }
        }

        throw new DteSignerException(
            'No formatter supports the provided key format',
            'COD_815'
        );
    }

    public function toPemDecrypted(string $key, ?string $password = null): string
    {
        foreach ($this->formatters as $formatter) {
            if ($formatter->supports($key)) {
                return $formatter->toPemDecrypted($key, $password);
            }
        }

        throw new DteSignerException(
            'No formatter supports the provided key format',
            'COD_815'
        );
    }
}
