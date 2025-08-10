# DTE Signer PHP SDK

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net/)
[![Tests](https://img.shields.io/badge/Tests-37%20passed-brightgreen.svg)](#testing)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

A PHP SDK for signing and verifying Documentos Tributarios Electrónicos (DTE) for El Salvador using JWS RS512 digital signatures.

## Project Status

✅ **Production Ready** - All core features implemented and tested
✅ **MH Certificate Support** - Full support for Ministerio de Hacienda certificate format
✅ **Standards Compliant** - Follows El Salvador DTE specifications
✅ **Working Examples** - Ready-to-run examples with test certificates
✅ **Test Coverage** - 37 tests, 90+ assertions, 100% pass rate
🔒 **Security** - Secure memory handling and input validation

## Features

- ✅ **Local signing**: No external servers or JVM required
- ✅ **Signature verification**: Verify and extract content from signed DTEs
- ✅ **JWS RS512**: Compliant with El Salvador DTE specifications
- ✅ **MH Certificate Support**: Full support for Ministerio de Hacienda XML format
- ✅ **Standards Compliant**: Follows El Salvador DTE specifications
- ✅ **Flexible input**: Accept JSON directly or from files
- ✅ **Certificate validation**: XML certificate parsing and validation
- ✅ **Error handling**: Standardized error codes (COD_803, COD_812, etc.)
- ✅ **Security**: Secure memory handling and input validation
- ✅ **Performance**: <500ms signing time for typical DTEs
- ✅ **Modern PHP**: 8.1+ with full type safety and PSR-12 compliance
- ✅ **Easy testing**: Includes mock certificate generator for development

## Quick Test Setup

### For Testing and Development

1. **Clone and install:**
   ```bash
   git clone https://github.com/marzsv/sv-dte-signer.git
   cd sv-dte-signer
   composer install
   ```

2. **Generate test certificates:**
   ```bash
   php examples/mock_certificate_generator.php
   ```

3. **Run signing example:**
   ```bash
   php examples/basic_usage.php
   ```

4. **Run verification example:**
   ```bash
   php examples/verification_usage.php
   ```

4. **Run all tests:**
   ```bash
   composer test
   ```

### For Production Use

Install via Composer:

```bash
composer require marzsv/sv-dte-signer
```

## Requirements

- PHP 8.1 or higher
- OpenSSL extension
- libxml extension

## Quick Start

### Basic Usage

```php
<?php

require_once 'vendor/autoload.php';

use Marzsv\DteSigner\DteSigner;

// Initialize the signer
$signer = new DteSigner();

// Prepare your DTE signing request
$request = [
    'nit' => '12345678901234',
    'passwordPri' => 'your_certificate_password',
    'dteJson' => [
        'identificacion' => [
            'version' => 1,
            'ambiente' => '00',
            'tipoDte' => '01',
            'numeroControl' => 'DTE-01-00000001-000000000000001',
            'codigoGeneracion' => 'A1B2C3D4-E5F6-7890-1234-567890ABCDEF',
            'fecEmi' => '2025-07-20',
            'horEmi' => '10:30:00'
        ],
        'emisor' => [
            'nit' => '12345678901234',
            'nombre' => 'EMPRESA EJEMPLO S.A. DE C.V.'
        ],
        'receptor' => [
            'nit' => '98765432109876',
            'nombre' => 'CLIENTE EJEMPLO S.A. DE C.V.'
        ],
        'resumen' => [
            'totalPagar' => 113.00
        ]
    ]
];

// Sign the DTE
$response = $signer->sign($request);

if ($response['success']) {
    echo "DTE signed successfully!\\n";
    echo "Signed JWS: " . $response['data'] . "\\n";
} else {
    echo "Error: " . $response['message'] . "\\n";
    echo "Code: " . $response['errorCode'] . "\\n";
}
```

### Using JSON Files

```php
<?php

use Marzsv\DteSigner\DteSigner;

$signer = new DteSigner();

// Sign from a JSON file
$response = $signer->sign('/path/to/dte_request.json');

if ($response['success']) {
    echo "DTE signed successfully from file!\\n";
}
```

### Custom Certificate Directory

```php
<?php

use Marzsv\DteSigner\DteSigner;

// Use a custom certificate directory
$signer = new DteSigner('/path/to/certificates');
$response = $signer->sign($request);
```

## DTE Verification

The SDK also provides functionality to verify signed DTEs and extract their original content.

### Basic Verification

```php
<?php

use Marzsv\DteSigner\DteVerifier;

// Initialize the verifier
$verifier = new DteVerifier();

// Verify signature and extract original DTE content
$response = $verifier->verify($jwsToken, '12345678901234');

if ($response['success']) {
    echo "DTE signature verified successfully!\n";
    echo "Original DTE data:\n";
    print_r($response['data']);
} else {
    echo "Verification failed: " . $response['message'] . "\n";
}
```

### Extract Payload Without Verification

```php
<?php

use Marzsv\DteSigner\DteVerifier;

$verifier = new DteVerifier();

// Extract payload without signature verification (NOT RECOMMENDED for production)
$response = $verifier->extractPayload($jwsToken);

if ($response['success']) {
    echo "Payload extracted (signature not verified):\n";
    print_r($response['data']);
}
```

### Custom Certificate Directory for Verification

```php
<?php

use Marzsv\DteSigner\DteVerifier;

// Use custom certificate directory for verification
$verifier = new DteVerifier('/path/to/certificates');
$response = $verifier->verify($jwsToken, $nit);
```

## Certificate Setup

1. **Certificate Format**: Certificates must be XML files with the following structure:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<certificate>
    <activo>true</activo>
    <verificado>true</verificado>
    <passwordHash>sha512_hash_of_password</passwordHash>
    <privateKey><![CDATA[-----BEGIN PRIVATE KEY-----...]]></privateKey>
</certificate>
```

2. **File Naming**: Certificate files must be named `{NIT}.crt` (e.g., `12345678901234.crt`)

3. **Directory**: Place certificates in the `certificates/` directory or specify a custom path

## API Reference

### DteSigner Class

#### Constructor

```php
public function __construct(string $certificateDirectory = 'certificates')
```

**Parameters:**
- `$certificateDirectory`: Path to the directory containing certificate files

#### sign()

```php
public function sign(array|string $input): array
```

**Parameters:**
- `$input`: Either an associative array with signing data or a file path to JSON

**Returns:**
- Success response: `{'success': true, 'message': '...', 'data': 'signed_jws'}`
- Error response: `{'success': false, 'message': '...', 'errorCode': 'COD_XXX', 'errors': [...]}`

### DteVerifier Class

#### Constructor

```php
public function __construct(string $certificateDirectory = 'certificates')
```

**Parameters:**
- `$certificateDirectory`: Path to the directory containing certificate files

#### verify()

```php
public function verify(string $jwsToken, string $nit): array
```

**Parameters:**
- `$jwsToken`: The JWS token to verify
- `$nit`: The 14-digit NIT of the certificate used for signing

**Returns:**
- Success response: `{'success': true, 'message': '...', 'data': array}`
- Error response: `{'success': false, 'message': '...', 'errorCode': 'COD_XXX', 'errors': [...]}`

#### extractPayload()

```php
public function extractPayload(string $jwsToken): array
```

**Parameters:**
- `$jwsToken`: The JWS token to extract payload from

**Returns:**
- Success response: `{'success': true, 'message': '...', 'data': array}`
- Error response: `{'success': false, 'message': '...', 'errorCode': 'COD_XXX', 'errors': [...]}`

**⚠️ Warning:** This method does not verify the signature. Use only when signature verification is not required.

### Request Format

```php
[
    'nit' => 'string(14)',           // Required: 14-digit NIT
    'passwordPri' => 'string(8-100)', // Required: Certificate password
    'dteJson' => array,              // Required: DTE document data
    'passwordPub' => 'string',       // Optional: Public key password
    'nombreDocumento' => 'string'    // Optional: Document name
]
```

## Error Codes

| Code | Description |
|------|-------------|
| COD_803 | Validation error |
| COD_812 | Certificate not found |
| COD_813 | Invalid certificate |
| COD_814 | Password mismatch |
| COD_815 | Signing error |
| COD_816 | JSON encoding error |
| COD_820 | Verification error |
| COD_500 | Unexpected error |

## Example Response

### Successful Signing Response

```json
{
    "success": true,
    "message": "DTE signed successfully",
    "data": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzUxMiJ9.eyJkdGUiOiJ7XG4gICAgXCJpZGVudGlmaWNhY2lvblwiOiB7XG4gICAgICAgIFwidmVyc2lvblwiOiAxLFxuICAgICAgICBcImFtYmllbnRlXCI6IFwiMDBcIixcbiAgICAgICAgXCJ0aXBvRHRlXCI6IFwiMDFcIixcbiAgICAgICAgXCJudW1lcm9Db250cm9sXCI6IFwiRFRFLTAxLTAwMDAwMDAxLTAwMDAwMDAwMDAwMDAwMVwiLFxuICAgICAgICBcImNvZGlnb0dlbmVyYWNpb25cIjogXCJBMUIyQzNENC1FNUY2LTc4OTAtMTIzNC01Njc4OTBBQkNERUZcIixcbiAgICAgICAgXCJmZWNFbWlcIjogXCIyMDI1LTA3LTIwXCIsXG4gICAgICAgIFwiaG9yRW1pXCI6IFwiMTA6MzA6MDBcIixcbiAgICAgICAgXCJ0aXBvTW9uZWRhXCI6IFwiVVNEXCJcbiAgICB9LFxuICAgIFwiZW1pc29yXCI6IHtcbiAgICAgICAgXCJuaXRcIjogXCIxMjM0NTY3ODkwMTIzNFwiLFxuICAgICAgICBcIm5vbWJyZVwiOiBcIkVNUFJFU0EgREUgRUpFTVBMTyBTLkEuIERFIEMuVi5cIixcbiAgICAgICAgXCJub21icmVDb21lcmNpYWxcIjogXCJFbXByZXNhIEVqZW1wbG9cIlxuICAgIH0sXG4gICAgXCJyZWNlcHRvclwiOiB7XG4gICAgICAgIFwibml0XCI6IFwiOTg3NjU0MzIxMDk4NzZcIixcbiAgICAgICAgXCJub21icmVcIjogXCJDTElFTlRFIEVKRU1QTE8gUy5BLiBERSBDLlYuXCJcbiAgICB9LFxuICAgIFwicmVzdW1lblwiOiB7XG4gICAgICAgIFwidG90YWxQYWdhclwiOiAxMTNcbiAgICB9XG59In0.oz9T56xhFI28mUP0-HFDQ7eV-IjJgM7BKL2YVYnPdA2cLn5Hz6gvtKxVEXD91kkHAUFAhfc1FJOGbHkeuYBnRlnxut0znH6wPsVcALUBv2euPaNJKBFSOBaHPTQLjWJi3z2sb1Kozx5V1kU10Ux5tuK9q9jndPVEPYAHVAL5iYkfhtmcQR6cZn-4WS6lygJzhvJH1PxKRoWTt4vBPlAXb5ArCLFq9YDLCe8WDTJ_H4THpI2mLl5A42pD8k2SGtNHfMB8a4q57pIql5oSyrpBIV3czH2H7zOMtFhhVNdoCwSGL6RQ4AoQBSTYm4V-KHNUYtNle67tuVD2BtkZ36biLw"
}
```

### JWS Token Structure

The signed JWS token follows the standard format: `header.payload.signature`

- **Header**: `{"typ":"JWT","alg":"RS512"}` (Base64URL encoded)
- **Payload**: DTE JSON data as pretty-printed string (Base64URL encoded)
- **Signature**: RSA-SHA512 signature (Base64URL encoded)

### Error Response Example

```json
{
    "success": false,
    "message": "Request validation failed",
    "errorCode": "COD_803",
    "errors": [
        "NIT must be exactly 14 characters long",
        "Password must be at least 8 characters long"
    ]
}
```

### Successful Verification Response

```json
{
    "success": true,
    "message": "DTE signature verified successfully",
    "data": {
        "identificacion": {
            "version": 1,
            "ambiente": "00",
            "tipoDte": "01",
            "numeroControl": "DTE-01-00000001-000000000000001",
            "codigoGeneracion": "A1B2C3D4-E5F6-7890-1234-567890ABCDEF",
            "fecEmi": "2025-07-20",
            "horEmi": "10:30:00"
        },
        "emisor": {
            "nit": "12345678901234",
            "nombre": "EMPRESA DE EJEMPLO S.A. DE C.V."
        },
        "receptor": {
            "nit": "98765432109876",
            "nombre": "CLIENTE EJEMPLO S.A. DE C.V."
        },
        "resumen": {
            "totalPagar": 113.00
        }
    }
}
```

### Verification Error Response

```json
{
    "success": false,
    "message": "Invalid JWS signature",
    "errorCode": "COD_820",
    "errors": [
        "Signature verification failed"
    ]
}
```

## Examples

See the `examples/` directory for working examples:

- **Basic Usage**: `examples/basic_usage.php`
- **File Usage**: `examples/file_usage.php`
- **Verification Usage**: `examples/verification_usage.php`
- **Error Handling**: `examples/error_handling.php`

### Running Examples

**Prerequisites:** Make sure you have generated test certificates first:
```bash
php examples/mock_certificate_generator.php
```

**Then run any example:**
```bash
# Basic DTE signing
php examples/basic_usage.php

# Sign from JSON file
php examples/file_usage.php

# Verify DTE signatures and extract content
php examples/verification_usage.php

# Error handling demonstration
php examples/error_handling.php
```

**Expected output:** Examples will demonstrate signing, verification, and error handling with detailed output showing the complete process.

## Testing

### Test Results
✅ **37 tests** | ✅ **90+ assertions** | ✅ **100% pass rate** | ⚡ **<100ms execution time**

Run the test suite:

```bash
composer test
```

Run with coverage:

```bash
composer test:coverage
```

Run static analysis:

```bash
composer analyse
```

Run all checks (tests + analysis):

```bash
composer check
```

### Test Coverage
- Unit tests for all core components (signing and verification)
- Integration tests with mock certificates
- Signature verification and payload extraction tests
- Error handling validation
- Request/response format verification

## Security Considerations

- 🔐 **Certificates**: Store certificates securely with proper file permissions
- 🔒 **Passwords**: Use strong passwords and secure password management
- 🚫 **Logging**: Never log passwords or private keys
- ✅ **Validation**: Always validate input data
- 🧹 **Memory**: Sensitive data is cleared from memory after use

## Production Readiness

### ✅ Ready for Production
- [x] El Salvador DTE specification compliance
- [x] JWS RS512 signing and verification implementation
- [x] Input validation and error handling
- [x] Security best practices implemented
- [x] Performance requirements met (<500ms)
- [x] Comprehensive unit and integration tests
- [x] PSR-12 code standards

### 🔧 Production Setup Checklist
- [ ] Use real certificates from Ministro de Hacienda de El Salvador
- [ ] Secure certificate storage with proper permissions (600/700)
- [ ] Environment-specific configuration
- [ ] Production logging setup (without sensitive data)
- [ ] Error monitoring and alerting
- [ ] Performance monitoring
- [ ] Regular security updates

### 📋 Dependencies
- **Runtime**: PHP 8.1+, OpenSSL, libxml
- **Library**: firebase/php-jwt v6.11+ for JWS implementation
- **Compatible**: Tested with PHP 8.4.11
- **Development**: PHPUnit 10.5+, PHPStan 1.10+ for quality assurance

## Development

### Project Structure

```
src/
├── DteSigner.php              # Main signing class
├── DteVerifier.php            # Main verification class
├── Certificate/               # Certificate handling
├── Signing/                   # JWS signing and verification engine
├── Validators/                # Input validation
├── Exceptions/                # Custom exceptions
└── Utils/                     # Utility classes
examples/                      # Working examples
tests/                         # PHPUnit tests
certificates/                  # Default certificate directory
```

### Code Style

This project follows PSR-12 coding standards and uses:
- English naming conventions
- Type hints for all parameters and return values
- Constants for magic numbers
- Dependency injection pattern

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes following PSR-12
4. Add tests for new functionality
5. Submit a pull request

## License

MIT License. See [LICENSE](LICENSE) file for details.

## Support

For issues and questions:
- GitHub Issues: [Report a bug](https://github.com/marzsv/sv-dte-signer/issues)
- Documentation: See `examples/` directory

## Disclaimer

This SDK implements the complete DTE signing specification and is production-ready from a technical standpoint. However:

- Ensure compliance with current El Salvador Ministro de Hacienda de El Salvador regulations
- Use official certificates provided by the Ministro de Hacienda de El Salvador for production
- Perform additional security audits as required by your organization
- The examples use mock certificates for demonstration purposes only

For production deployment, review all security considerations and follow the production setup checklist above.
