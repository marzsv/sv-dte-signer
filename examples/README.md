# DTE Signer Examples

This directory contains working examples demonstrating how to use the DTE Signer PHP SDK.

## Getting Started

1. **Install dependencies:**
   ```bash
   composer install
   ```

2. **Generate a test certificate:**
   ```bash
   php examples/mock_certificate_generator.php
   ```

3. **Run the examples:**

### Basic Usage Example
Shows how to sign a DTE using direct JSON data:
```bash
php examples/basic_usage.php
```

### File Usage Example
Shows how to sign a DTE using a JSON file:
```bash
php examples/file_usage.php
```

### Error Handling Example
Demonstrates various error scenarios:
```bash
php examples/error_handling.php
```

## Files Description

- **`mock_certificate_generator.php`** - Generates test certificates for development
- **`sample_dte_request.json`** - Complete example of a DTE signing request
- **`basic_usage.php`** - Basic signing with array input
- **`file_usage.php`** - Signing from JSON file
- **`error_handling.php`** - Error scenarios and handling

## Test Data

The examples use the following test data:
- **Test NIT:** `12345678901234`
- **Test Password:** `testpassword`

## Production Usage

⚠️ **Important:** These examples use mock certificates for testing only. In production:

1. Use real certificates provided by the Ministry of Finance of El Salvador
2. Store certificates securely with proper file permissions
3. Use strong passwords and secure password management
4. Validate all input data thoroughly
5. Implement proper logging (without sensitive data)

## Error Codes

The SDK uses standard error codes as specified in the DTE documentation:
- `COD_803` - Validation error
- `COD_812` - Certificate not found
- `COD_813` - Invalid certificate
- `COD_814` - Password mismatch
- `COD_815` - Signing error
- `COD_816` - JSON encoding error