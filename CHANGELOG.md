# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.5.0] - 2026-06-06

### Added
- **Interfaces & Contracts**: Formalized component contracts with `CertificateLoaderInterface`, `JwsSignerInterface`, `JwsVerifierInterface`, `KeyFormatterInterface`, and `RateLimiterInterface`
- **Strategy Pattern**: Refactored `KeyFormatter` into pluggable strategies (`PemKeyFormatter`, `DerKeyFormatter`, `CompositeKeyFormatter`) for extensible key format handling
- **Factory Pattern**: Added `DteSignerFactory` with fluent builder interface for centralized configuration of signer/verifier instances
- **Rate Limiting**: Implemented `RateLimiterInterface` with three implementations:
  - `NullRateLimiter`: No-op rate limiter (default)
  - `InMemoryRateLimiter`: In-memory tracking within process
  - `FileRateLimiter`: File-based persistence for multi-process environments (PHP-FPM)
- **PSR-3 Audit Logging**: Full audit logging integration with `LoggerInterface` for signing and verification operations
  - Logs operation start/success/failure with NIT and error codes
  - Security-conscious: never logs passwords, private keys, or full tokens
  - Uses MD5 hashes for token identification
- **PHPStan Level 9**: Upgraded static analysis to maximum strictness with comprehensive type fixes

### Changed
- `DteSigner` and `DteVerifier` now accept optional `LoggerInterface` and rate limiter parameters
- `CertificateLoader`, `JwsSigner`, and `JwsVerifier` now implement formal interfaces
- `JwsSigner` and `CertificateLoader` now use injected `KeyFormatterInterface` instead of static `KeyFormatter`
- Updated `composer.json` to require `psr/log: ^3.0`
- Enhanced `phpstan.neon` configuration to level 9

### Security
- Added rate limiting to protect against brute force verification attempts
- Integrated PSR-3 logging for complete audit trail without exposing sensitive data
- All changes maintain existing security standards while adding new protections

### Backward Compatibility
- All changes are 100% backward compatible
- Static `KeyFormatter` class maintained for legacy code
- Default implementations use no-op behaviors (NullLogger, NullRateLimiter)
- Existing code requires zero modifications to continue working

## [1.4.0] - 2026-05-15

### Added
- Add backward compatibility for deprecated `passwordPri` field

### Changed
- Clean up validators, add XML error handling, centralize config

## [1.3.0] - 2026-05-10

### Added
- Performance improvement recommendations (P1-P6)

## [1.2.0] - 2026-05-05

### Changed
- Extract duplicate code and centralize NIT validation (Phase 3)

## [1.1.0] - 2026-05-01

### Added
- Initial DTE verification capabilities

## [1.0.0] - 2026-04-01

### Added
- Initial release of PHP DTE Signer SDK
- Support for signing Documentos Tributarios Electrónicos (DTE)
- JWS RS512 digital signature support
- XML certificate parsing and validation
- Basic verification capabilities
