<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Security;

use Marzsv\DteSigner\Contracts\RateLimiterInterface;

/**
 * File-based rate limiter that persists state to disk
 *
 * Suitable for multi-process environments (e.g., PHP-FPM) where
 * in-memory state cannot be shared between requests.
 */
class FileRateLimiter implements RateLimiterInterface
{
    private string $filePath;
    private int $maxAttempts;
    private int $windowSeconds;

    public function __construct(string $filePath, int $maxAttempts = 5, int $windowSeconds = 300)
    {
        $this->filePath = $filePath;
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds;
    }

    public function isAllowed(string $identifier): bool
    {
        $attempts = $this->loadAttempts();
        $now = time();
        $windowStart = $now - $this->windowSeconds;

        if (!isset($attempts[$identifier])) {
            return true;
        }

        $recentAttempts = array_filter(
            $attempts[$identifier],
            static fn(int $timestamp) => $timestamp > $windowStart
        );

        return count($recentAttempts) < $this->maxAttempts;
    }

    public function recordAttempt(string $identifier): void
    {
        $attempts = $this->loadAttempts();
        $now = time();
        $windowStart = $now - $this->windowSeconds;

        if (!isset($attempts[$identifier])) {
            $attempts[$identifier] = [];
        }

        $attempts[$identifier][] = $now;

        $attempts[$identifier] = array_filter(
            $attempts[$identifier],
            static fn(int $timestamp) => $timestamp > $windowStart
        );

        $this->saveAttempts($attempts);
    }

    public function reset(string $identifier): void
    {
        $attempts = $this->loadAttempts();
        unset($attempts[$identifier]);
        $this->saveAttempts($attempts);
    }

    /**
     * Load attempts from file
     *
     * @return array<string, array<int>>
     */
    private function loadAttempts(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $handle = fopen($this->filePath, 'r');
        if ($handle === false) {
            return [];
        }

        if (!flock($handle, LOCK_SH)) {
            fclose($handle);
            return [];
        }

        $content = stream_get_contents($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        if ($content === false || $content === '') {
            return [];
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            return [];
        }

        /** @var array<string, array<int>> $data */
        return $data;
    }

    /**
     * Save attempts to file
     *
     * @param array<string, array<int>> $attempts
     */
    private function saveAttempts(array $attempts): void
    {
        $dir = dirname($this->filePath);
        if ($dir !== '' && !is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }

        $handle = fopen($this->filePath, 'w');
        if ($handle === false) {
            return;
        }

        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            return;
        }

        $json = json_encode($attempts);
        if ($json !== false) {
            fwrite($handle, $json);
        }

        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
