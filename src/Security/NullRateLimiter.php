<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Security;

use Marzsv\DteSigner\Contracts\RateLimiterInterface;

/**
 * No-op rate limiter that always allows attempts
 *
 * Use when rate limiting is not needed or handled externally.
 */
class NullRateLimiter implements RateLimiterInterface
{
    public function isAllowed(string $identifier): bool
    {
        return true;
    }

    public function recordAttempt(string $identifier): void
    {
    }

    public function reset(string $identifier): void
    {
    }
}
