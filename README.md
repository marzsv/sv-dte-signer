# DTE Signer PHP SDK

A PHP SDK for signing Documentos Tributarios Electrónicos (DTE) for El Salvador using JWS RS512 digital signatures.

## Features

- ✅ **Local signing**: No external servers or JVM required
- ✅ **JWS RS512**: Compliant with El Salvador DTE specifications
- ✅ **Flexible input**: Accept JSON directly or from files
- ✅ **Certificate validation**: XML certificate parsing and validation
- ✅ **Error handling**: Standardized error codes and responses
- ✅ **Security**: Secure password hashing and memory management
- ✅ **PHP 8.1+**: Modern PHP with type safety

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

3. **Directory**: Place certificates in the `uploads/` directory or specify a custom path

## API Reference

### DteSigner Class

#### Constructor

```php
public function __construct(string $certificateDirectory = 'uploads')
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

Run the test suite:

```bash
composer test
```

Run with coverage:

```bash
composer test:coverage
```

## Security Considerations

- 🔐 **Certificates**: Store certificates securely with proper file permissions
- 🔒 **Passwords**: Use strong passwords and secure password management
- 🚫 **Logging**: Never log passwords or private keys
- ✅ **Validation**: Always validate input data
- 🧹 **Memory**: Sensitive data is cleared from memory after use

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
uploads/                       # Default certificate directory
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

This SDK is for educational and development purposes. Ensure compliance with El Salvador's Ministry of Finance regulations when using in production.# php-dte-signer
# php-dte-signer
