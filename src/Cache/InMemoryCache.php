<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Cache;

use Marzsv\DteSigner\Contracts\CacheInterface;

/**
 * Simple in-memory cache implementation
 */
class InMemoryCache implements CacheInterface
{
    /** @var array<string, mixed> */
    private array $store = [];

    public function get(string $key): mixed
    {
        return $this->store[$key] ?? null;
    }

    public function put(string $key, mixed $value): void
    {
        $this->store[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($this->store[$key]);
    }

    public function forget(string $key): void
    {
        unset($this->store[$key]);
    }

    public function flush(): void
    {
        $this->store = [];
    }
}
