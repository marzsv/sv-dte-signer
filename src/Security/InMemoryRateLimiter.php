<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Security;

use Marzsv\DteSigner\Contracts\RateLimiterInterface;

/**
 * In-memory rate limiter that tracks attempts within a time window
 *
 * Useful for single-process environments. Does not persist between requests.
 */
class InMemoryRateLimiter implements RateLimiterInterface
{
    private int $maxAttempts;
    private int $windowSeconds;
    /** @var array<string, array<int>> Timestamps of attempts for each identifier */
    private static array $attempts = [];

    public function __construct(int $maxAttempts = 5, int $windowSeconds = 300)
    {
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds;
    }

    public function isAllowed(string $identifier): bool
    {
        $now = time();
        $windowStart = $now - $this->windowSeconds;

        if (!isset(self::$attempts[$identifier])) {
            return true;
        }

        $recentAttempts = array_filter(
            self::$attempts[$identifier],
            static fn(int $timestamp) => $timestamp > $windowStart
        );

        return count($recentAttempts) < $this->maxAttempts;
    }

    public function recordAttempt(string $identifier): void
    {
        $now = time();

        if (!isset(self::$attempts[$identifier])) {
            self::$attempts[$identifier] = [];
        }

        self::$attempts[$identifier][] = $now;

        $windowStart = $now - $this->windowSeconds;
        self::$attempts[$identifier] = array_filter(
            self::$attempts[$identifier],
            static fn(int $timestamp) => $timestamp > $windowStart
        );
    }

    public function reset(string $identifier): void
    {
        unset(self::$attempts[$identifier]);
    }
}
