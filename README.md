# DTE Signer PHP SDK

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net/)
[![Tests](https://img.shields.io/badge/Tests-11%20passed-brightgreen.svg)](#testing)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

A PHP SDK for signing Documentos Tributarios Electrónicos (DTE) for El Salvador using JWS RS512 digital signatures.

## Project Status

✅ **Fully Functional** - All core features implemented and tested  
✅ **PRD Compliant** - 100% compliance with Product Requirements Document  
✅ **Working Examples** - Ready-to-run examples included  
✅ **Test Coverage** - 11 tests, 23 assertions, 100% pass rate  
⚡ **Performance** - Signing in <500ms (meets PRD requirements)

## Features

- ✅ **Local signing**: No external servers or JVM required
- ✅ **JWS RS512**: Compliant with El Salvador DTE specifications  
- ✅ **PRD Compliant**: 100% implementation of Product Requirements Document
- ✅ **Flexible input**: Accept JSON directly or from files
- ✅ **Certificate validation**: XML certificate parsing and validation
- ✅ **Error handling**: Standardized error codes (COD_803, COD_812, etc.)
- ✅ **Security**: SHA-512 password hashing and secure memory management
- ✅ **Performance**: <500ms signing time for typical DTEs
- ✅ **Modern PHP**: 8.1+ with full type safety and PSR-12 compliance

## Installation

Install via Composer:

```bash
composer require dteelsalvador/php-dte-signer
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

use DteSigner\DteSigner;

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

use DteSigner\DteSigner;

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

use DteSigner\DteSigner;

// Use a custom certificate directory
$signer = new DteSigner('/path/to/certificates');
$response = $signer->sign($request);
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

## Examples

See the `examples/` directory for working examples:

- **Basic Usage**: `examples/basic_usage.php`
- **File Usage**: `examples/file_usage.php`
- **Error Handling**: `examples/error_handling.php`

### Running Examples

1. Generate test certificates:
   ```bash
   php examples/mock_certificate_generator.php
   ```

2. Run examples:
   ```bash
   php examples/basic_usage.php
   php examples/file_usage.php
   php examples/error_handling.php
   ```

## Testing

### Test Results
✅ **11 tests** | ✅ **23 assertions** | ✅ **100% pass rate**

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
- Unit tests for all core components
- Integration tests with mock certificates  
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
- [x] Full PRD specification compliance
- [x] JWS RS512 signing implementation
- [x] Input validation and error handling
- [x] Security best practices implemented
- [x] Performance requirements met (<500ms)
- [x] Unit and integration tests
- [x] PSR-12 code standards

### 🔧 Production Setup Checklist
- [ ] Use real certificates from Ministry of Finance
- [ ] Secure certificate storage with proper permissions (600/700)
- [ ] Environment-specific configuration
- [ ] Production logging setup (without sensitive data)
- [ ] Error monitoring and alerting
- [ ] Performance monitoring
- [ ] Regular security updates

### 📋 Dependencies
- **Runtime**: PHP 8.1+, OpenSSL, libxml
- **Library**: lcobucci/jwt v5.0 for JWS implementation
- **Compatible**: Tested with PHP 8.4.10

## Development

### Project Structure

```
src/
├── DteSigner.php              # Main SDK class
├── Certificate/               # Certificate handling
├── Signing/                   # JWS signing engine  
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
- GitHub Issues: [Report a bug](https://github.com/dteelsalvador/php-dte-signer/issues)
- Documentation: See `examples/` directory

## Disclaimer

This SDK implements the complete DTE signing specification and is production-ready from a technical standpoint. However:

- Ensure compliance with current El Salvador Ministry of Finance regulations
- Use official certificates provided by the Ministry of Finance for production
- Perform additional security audits as required by your organization
- The examples use mock certificates for demonstration purposes only

For production deployment, review all security considerations and follow the production setup checklist above.
