# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a PHP SDK for signing Documentos Tributarios Electrónicos (DTE) for El Salvador using JWS RS512 digital signatures. The project provides a Composer-installable package that enables local DTE signing without external Java dependencies.

## Development Commands

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run tests with coverage
composer test:coverage

# Run static analysis (PHPStan level 8)
composer analyse

# Run all checks (analysis + tests)
composer check
```

### Individual Test Commands
```bash
# Run specific test file
phpunit tests/Unit/RequestValidatorTest.php

# Run with verbose output
phpunit --verbose

# Generate coverage report
phpunit --coverage-html tests/coverage/html
```

## Architecture

The SDK follows a layered architecture with clear separation of concerns:

### Core Components

- **`DteSigner`** (main class): Entry point that orchestrates the signing process
- **Request Validation**: `RequestValidator` validates input data (NIT, passwords, DTE JSON)
- **Certificate Management**: 
  - `CertificateLoader`: Loads XML certificates from filesystem
  - `CertificateParser`: Parses XML certificate structure
  - `CertificateValidator`: Validates certificate data and password hashes
- **Signing Engine**: `JwsSigner` creates JWS RS512 signatures using lcobucci/jwt
- **Response Handling**: `ResponseBuilder` formats success/error responses
- **Exception System**: Custom exception hierarchy with standardized error codes

### Data Flow
```
Input (JSON/File) → RequestValidator → CertificateLoader → JwsSigner → ResponseBuilder
```

### Certificate System
- Certificates stored as XML files named `{NIT}.crt` in configurable directory
- SHA-512 password hashing for validation
- RSA private keys in PKCS#8 format for JWS signing

## Key Implementation Details

### Error Codes
The project implements standardized error codes from the PRD specification:
- `COD_803`: Validation errors
- `COD_812`: Certificate not found
- `COD_813`: Invalid certificate
- `COD_814`: Password mismatch
- `COD_815`: Signing errors
- `COD_816`: JSON encoding errors
- `COD_500`: Unexpected errors

### Security Features
- Sensitive data (passwords) cleared from memory after use
- Input validation with regex patterns for NIT format
- Never logs passwords or private keys
- Secure memory handling throughout the signing process

### Dependencies
- **Runtime**: `firebase/php-jwt` v6.11+ for JWS implementation (migrated from lcobucci/jwt for better long-term support)
- **Development**: PHPUnit v10 for testing, PHPStan v1.10 for static analysis
- **Extensions**: OpenSSL (for cryptography), libxml (for XML parsing)

## Testing Strategy

### Test Structure
- **Unit Tests** (`tests/Unit/`): Individual component testing
- **Integration Tests** (`tests/Integration/`): End-to-end signing workflows  
- **Mock Data**: Examples use generated test certificates for demonstration

### Test Execution
- PHPUnit 10 with strict error reporting
- Coverage reporting in HTML, text, and Clover formats
- JUnit XML output for CI integration

## Code Standards

The project strictly follows:
- **PHP 8.1+** with strict typing (`declare(strict_types=1)`)
- **PSR-12** code formatting standards  
- **English naming** for all classes, methods, variables
- **Type hints** for all parameters and return values
- **Constants** instead of magic numbers (per CLAUDE.md conventions)
- **Dependency injection** pattern throughout

### Architecture Patterns
- Constructor injection for dependencies
- Interface segregation in component design
- Single responsibility principle for each class
- Exception-based error handling with custom exception hierarchy

## Examples and Usage

Working examples in `examples/` directory:
- `basic_usage.php`: Sign DTE with direct JSON data
- `file_usage.php`: Sign DTE from JSON file
- `error_handling.php`: Demonstrates error scenarios
- `mock_certificate_generator.php`: Creates test certificates

Run examples after generating test certificates:
```bash
php examples/mock_certificate_generator.php
php examples/basic_usage.php
```

## Production Considerations

- Replace example certificates with real ones from Ministry of Finance
- Secure certificate directory with proper file permissions (600/700)
- Environment-specific configuration for certificate paths
- Performance monitoring (signing should complete in <500ms)
- Error monitoring without logging sensitive data