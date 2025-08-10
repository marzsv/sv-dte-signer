# Product Requirements Document (PRD)

## 1. Document Information

- **Product Name**: DTE Signer PHP SDK
- **Version**: 1.0
- **Date**: July 19, 2025
- **Author**: Grok (based on user specifications and technical analysis)
- **Status**: Draft
- **Revision History**:
  | Version | Date       | Changes |
  |---------|------------|---------|
  | 1.0     | 2025-07-19 | Initial draft |

## 2. Overview

### 2.1 Purpose
This PRD outlines the requirements for developing a Composer-installable PHP SDK (Software Development Kit) for signing Documentos Tributarios Electrónicos (DTE) in El Salvador. The SDK will implement digital signing using JSON Web Signature (JWS) with RS512 (RSA-SHA512) algorithm, as per the provided technical specification. It addresses the need to perform local signing within PHP applications, eliminating dependencies on external Java-based tools provided by the Ministry of Finance. This enables seamless integration into existing PHP workflows for generating and signing DTEs, improving efficiency, reducing latency, and simplifying deployment.

The SDK will support two input methods for the DTE content:
- Direct JSON object or string.
- Path to a JSON file.

It will handle certificate loading, validation, signing, and error responses, ensuring compliance with El Salvador's electronic invoicing regulations (e.g., DTE formats like invoices, credit notes).<grok:render card_id="2ac85f" card_type="citation_card" type="render_inline_citation">
<argument name="citation_id">3</argument>
</grok:render> The package will be distributed via Packagist for easy installation via Composer.

### 2.2 Background
Current implementations rely on a Java application from the Ministry of Finance, requiring a separate JVM server and HTTP requests for signing. This introduces latency, maintenance overhead, and technological silos. By porting the signing logic to PHP, the SDK allows in-process signing, leveraging native PHP extensions (e.g., OpenSSL) and libraries for cryptography.

### 2.3 Target Audience
- PHP developers building applications for El Salvadoran taxpayers (e.g., ERP systems, e-invoicing platforms).
- Businesses required to issue DTEs under Ministry regulations.<grok:render card_id="bd395a" card_type="citation_card" type="render_inline_citation">
<argument name="citation_id">1</argument>
</grok:render>
- Open-source contributors interested in electronic invoicing tools.

### 2.4 Key Benefits
- Local signing: No external servers or JVM needed.
- Flexibility: Accept JSON directly or from files.
- Compliance: Adheres to JWS RS512, certificate structures, and error codes from the spec.
- Ease of Use: Simple API with Composer integration.

## 3. Objectives

- **Business Objectives**:
  - Reduce operational costs by eliminating separate Java infrastructure.
  - Improve performance by enabling instant, local DTE signing.
  - Ensure regulatory compliance for DTE issuance in El Salvador.

- **Technical Objectives**:
  - Implement full signing flow: Validation, certificate handling, JWS generation.
  - Support PHP 8.1+ for modern environments.
  - Achieve 100% test coverage for critical paths (e.g., cryptography, validation).

- **Success Metrics**:
  - SDK adoption: Measured by Packagist downloads.
  - Reliability: <5% error rate in production signing.
  - Performance: Signing time <500ms for typical DTEs.

## 4. Scope

### 4.1 In Scope
- Parsing and validation of signing requests (NIT, passwords, DTE JSON).
- Loading and validating XML certificates from configurable directories.
- SHA-512 password hashing and verification.
- JWS RS512 signing with RSA private keys (PKCS#8 format).
- Input options: Direct JSON or file path.
- Standardized JSON responses for success/errors (with codes like COD_803).
- Basic logging (without sensitive data).
- Unit/integration tests covering spec scenarios (section 11).
- Composer package structure with autoloading.

### 4.2 Out of Scope
- DTE generation/validation (only signing; assume valid input JSON).
- Integration with Ministry APIs for submission (e.g., API de Recepción).<grok:render card_id="881ba8" card_type="citation_card" type="render_inline_citation">
<argument name="citation_id">17</argument>
</grok:render>
- Public key verification or decryption features.
- GUI/CLI tools (focus on SDK; CLI can be a future extension).
- Support for other countries' e-invoicing formats.

## 5. Functional Requirements

### 5.1 Core Features
- **Signing Request Handling**:
  - Accept input as:
    - JSON string/object (e.g., `{"nit": "12345678901234", "passwordPri": "secret", "dteJson": {...}}`).
    - Or file path (e.g., `/path/to/request.json`), which loads and parses the JSON.
  - Validate mandatory fields: NIT (14 digits), passwordPri (8-100 chars), dteJson (non-null).
  - Optional fields: passwordPub, nombreDocumento, etc., as per spec section 4.

- **Certificate Management**:
  - Load certificate from `{NIT}.crt` in a configurable directory (default: "certificates").
  - Parse XML structure using SimpleXML or DOMDocument.
  - Validate: activo=true, verificado=true, privateKey present, password hash match (SHA-512).

- **Signing Process**:
  - Convert DTE JSON to pretty-printed string (UTF-8).
  - Create JWS: Header `{"alg": "RS512"}`, payload Base64URL, signature with RSA private key.
  - Output compact serialization: `header.payload.signature`.
  - Handle errors with spec codes (e.g., COD_812 for missing file).

- **Response Generation**:
  - Success: `{"success": true, "message": "...", "data": "signedJWS"}`.
  - Error: `{"success": false, "message": "...", "errorCode": "COD_XXX", "errors": [...]}`.

### 5.2 API Design
The SDK will expose a primary class `DteSigner` with methods:

```php
namespace DteSigner;

class DteSigner {
    public function __construct(string $certDir = 'certificates') {
        // Initialize with certificate directory
    }

    public function sign(array|string $input): array {
        // $input can be assoc array (JSON data) or string (file path)
        // Returns response array (success or error)
    }

    // Example usage:
    $signer = new DteSigner();
    $response = $signer->sign([
        'nit' => '12345678901234',
        'passwordPri' => 'password',
        'dteJson' => ['document' => 'content']
    ]);

    // Or with file:
    $response = $signer->sign('/path/to/request.json');
}
```

- Exceptions: Throw custom exceptions for invalid inputs, mapped to error responses.

### 5.3 User Stories
- As a developer, I want to sign a DTE by passing JSON data, so I can integrate it into my app workflow.
- As a developer, I want to sign from a file path, so I can handle batch files easily.
- As a user, I want detailed error messages with codes, so I can debug issues quickly.

## 6. Non-Functional Requirements

### 6.1 Performance
- Signing latency: <1 second for 2048-bit RSA keys and <10KB DTEs.
- Scalability: Handle 100+ concurrent signings (thread-safe where possible).

### 6.2 Security
- Never store/log passwords or keys in plain text.
- Use secure memory handling (e.g., unset sensitive vars).
- Validate all inputs to prevent injection (e.g., regex for NIT).
- Comply with spec section 8: Hash passwords, restrict file access.

### 6.3 Compatibility
- PHP: 8.1+ (with OpenSSL extension).
- OS: Linux/Mac/Windows (cross-platform).
- Frameworks: Agnostic, but testable with Laravel/Symfony.

### 6.4 Reliability
- Error handling: Graceful failures with spec codes.
- Logging: Configurable (e.g., PSR-3 compatible).

### 6.5 Maintainability
- Code style: PSR-12 compliant.
- Documentation: README.md with examples, API docs via PHPDoc.

## 7. Dependencies

- **PHP Extensions**: OpenSSL (for RSA/SHA512), libxml (for XML parsing).
- **Composer Packages**:
  - lcobucci/jwt: For JWS implementation (RS512 support).
  - Optional: monolog/monolog for logging.
- **Dev Dependencies**: PHPUnit for testing, PHPStan for static analysis.

Inspired by existing SDK patterns (e.g., JustSteveKing/php-sdk for API wrappers).<grok:render card_id="9ed5ed" card_type="citation_card" type="render_inline_citation">
<argument name="citation_id">21</argument>
</grok:render>

## 8. Architecture

High-level components:
- **Input Parser**: Handles JSON/string or file loading.
- **Validator**: Checks request and certificate per spec flowcharts.
- **Certificate Loader**: XML parsing and key extraction.
- **Signer Engine**: JWS creation using library/OpenSSL.
- **Response Builder**: Formats output.

Diagram (text-based):
```
Input (JSON/File) -> Parser -> Validator -> Certificate Loader -> Signer -> Response
```

## 9. Testing

- **Unit Tests**: Cover individual components (e.g., password hash, JWS generation).
- **Integration Tests**: End-to-end signing with mock certificates.
- **Cases**: From spec section 11 (e.g., valid/invalid NIT, missing cert).
- Tools: PHPUnit, with coverage >90%.

## 10. Deployment and Release

- **Packaging**: composer.json with autoload (PSR-4), license (MIT), keywords ("dte", "el-salvador", "electronic-invoice").
- **Release Process**: Tag versions on GitHub, submit to Packagist.
- **Documentation**: GitHub README with installation (`composer require vendor/dte-signer`), examples, and changelog.

## 11. Risks and Assumptions

- **Assumptions**: Certificates are pre-generated and stored securely; DTE JSON is valid.
- **Risks**: Changes in Ministry specs; mitigated by versioning.
- **Dependencies**: No external APIs needed for signing.

## 12. Next Steps

- Design phase: Wireframe API.
- Development: Implement core in 2-4 weeks.
- Testing and Review: 1 week.
- Release: Publish v1.0 to Packagist.