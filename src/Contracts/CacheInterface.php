<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Contracts;

/**
 * Cache interface for storing and retrieving data
 */
interface CacheInterface
{
    /**
     * Get value from cache
     *
     * @return mixed The cached value or null if not found
     */
    public function get(string $key): mixed;

    /**
     * Store value in cache
     */
    public function put(string $key, mixed $value): void;

    /**
     * Check if key exists in cache
     */
    public function has(string $key): bool;

    /**
     * Remove value from cache
     */
    public function forget(string $key): void;

    /**
     * Clear all cache
     */
    public function flush(): void;
}
