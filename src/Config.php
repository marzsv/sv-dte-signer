<?php

declare(strict_types=1);

namespace Marzsv\DteSigner;

/**
 * Centralized configuration constants for the DTE Signer library
 */
final class Config
{
    /**
     * Default directory where certificate files are stored
     */
    public const DEFAULT_CERTIFICATE_DIRECTORY = 'certificates';

    /**
     * Prevent instantiation
     */
    private function __construct()
    {
    }
}
