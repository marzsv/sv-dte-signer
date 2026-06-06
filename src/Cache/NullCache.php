<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Cache;

use Marzsv\DteSigner\Contracts\CacheInterface;

/**
 * Null cache implementation (no-op) - maintains original behavior
 */
class NullCache implements CacheInterface
{
    public function get(string $key): mixed
    {
        return null;
    }

    public function put(string $key, mixed $value): void
    {
    }

    public function has(string $key): bool
    {
        return false;
    }

    public function forget(string $key): void
    {
    }

    public function flush(): void
    {
    }
}
