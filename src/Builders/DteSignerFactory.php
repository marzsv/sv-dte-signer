<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Builders;

use Marzsv\DteSigner\Config;
use Marzsv\DteSigner\Certificate\CertificateLoader;
use Marzsv\DteSigner\Contracts\KeyFormatterInterface;
use Marzsv\DteSigner\Contracts\RateLimiterInterface;
use Marzsv\DteSigner\DteSigner;
use Marzsv\DteSigner\DteVerifier;
use Marzsv\DteSigner\Security\NullRateLimiter;
use Marzsv\DteSigner\Signing\JwsSigner;
use Marzsv\DteSigner\Signing\JwsVerifier;
use Marzsv\DteSigner\Utils\Formatters\CompositeKeyFormatter;
use Marzsv\DteSigner\Validators\RequestValidator;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Factory for creating and configuring DteSigner and DteVerifier instances
 *
 * Provides a fluent builder interface for configuring all components centrally.
 */
class DteSignerFactory
{
    private string $certificateDirectory;
    private LoggerInterface $logger;
    private RateLimiterInterface $rateLimiter;
    private KeyFormatterInterface $keyFormatter;

    private function __construct(string $certificateDirectory)
    {
        $this->certificateDirectory = $certificateDirectory;
        $this->logger = new NullLogger();
        $this->rateLimiter = new NullRateLimiter();
        $this->keyFormatter = new CompositeKeyFormatter();
    }

    /**
     * Create a factory for the given certificate directory
     */
    public static function forDirectory(string $certificateDirectory): self
    {
        return new self($certificateDirectory);
    }

    /**
     * Set the logger for operations
     */
    public function withLogger(LoggerInterface $logger): self
    {
        $clone = clone $this;
        $clone->logger = $logger;

        return $clone;
    }

    /**
     * Set the rate limiter for verification attempts
     */
    public function withRateLimiter(RateLimiterInterface $rateLimiter): self
    {
        $clone = clone $this;
        $clone->rateLimiter = $rateLimiter;

        return $clone;
    }

    /**
     * Set the key formatter for handling private key formats
     */
    public function withKeyFormatter(KeyFormatterInterface $keyFormatter): self
    {
        $clone = clone $this;
        $clone->keyFormatter = $keyFormatter;

        return $clone;
    }

    /**
     * Set the certificate directory
     */
    public function withDirectory(string $certificateDirectory): self
    {
        $clone = clone $this;
        $clone->certificateDirectory = $certificateDirectory;

        return $clone;
    }

    /**
     * Build a fully configured DteSigner instance
     */
    public function buildSigner(): DteSigner
    {
        return new DteSigner(
            $this->certificateDirectory,
            new RequestValidator(),
            new CertificateLoader($this->certificateDirectory, null, null, $this->logger, $this->keyFormatter),
            new JwsSigner($this->keyFormatter),
            $this->logger
        );
    }

    /**
     * Build a fully configured DteVerifier instance
     */
    public function buildVerifier(): DteVerifier
    {
        return new DteVerifier(
            $this->certificateDirectory,
            new CertificateLoader($this->certificateDirectory, null, null, $this->logger, $this->keyFormatter),
            new JwsVerifier(),
            $this->logger,
            $this->rateLimiter
        );
    }
}
