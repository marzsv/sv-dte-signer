<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Contracts;

/**
 * Contract for rate limiting verification attempts
 */
interface RateLimiterInterface
{
    /**
     * Check if an identifier is allowed to proceed
     *
     * @param string $identifier The identifier to check (e.g., NIT for DTE verification)
     * @return bool True if allowed, false if rate limit exceeded
     */
    public function isAllowed(string $identifier): bool;

    /**
     * Record an attempt for the given identifier
     *
     * @param string $identifier The identifier to record attempt for
     */
    public function recordAttempt(string $identifier): void;

    /**
     * Reset the counter for an identifier (e.g., after successful verification)
     *
     * @param string $identifier The identifier to reset
     */
    public function reset(string $identifier): void;
}
