<?php

declare(strict_types=1);

/**
 * Error Handling Example
 * 
 * This example demonstrates various error scenarios and how they are handled.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DteSigner\DteSigner;

echo "=== DTE Signer - Error Handling Examples ===\n\n";

$signer = new DteSigner();

// Example 1: Invalid NIT (not 14 digits)
echo "1. Testing invalid NIT (too short):\n";
$invalidNitRequest = [
    'nit' => '123456789',  // Only 9 digits instead of 14
    'passwordPri' => 'testpassword',
    'dteJson' => ['test' => 'data']
];

$response = $signer->sign($invalidNitRequest);
echo "   Result: " . ($response['success'] ? '✅ Success' : '❌ Error') . "\n";
echo "   Code: " . ($response['errorCode'] ?? 'N/A') . "\n";
echo "   Message: " . $response['message'] . "\n\n";

// Example 2: Missing required fields
echo "2. Testing missing required fields:\n";
$missingFieldsRequest = [
    'nit' => '12345678901234',
    // Missing passwordPri and dteJson
];

$response = $signer->sign($missingFieldsRequest);
echo "   Result: " . ($response['success'] ? '✅ Success' : '❌ Error') . "\n";
echo "   Code: " . ($response['errorCode'] ?? 'N/A') . "\n";
echo "   Message: " . $response['message'] . "\n";
if (!empty($response['errors'])) {
    echo "   Errors:\n";
    foreach ($response['errors'] as $error) {
        echo "   - " . $error . "\n";
    }
}
echo "\n";

// Example 3: Invalid password length
echo "3. Testing invalid password (too short):\n";
$shortPasswordRequest = [
    'nit' => '12345678901234',
    'passwordPri' => 'short',  // Less than 8 characters
    'dteJson' => ['test' => 'data']
];

$response = $signer->sign($shortPasswordRequest);
echo "   Result: " . ($response['success'] ? '✅ Success' : '❌ Error') . "\n";
echo "   Code: " . ($response['errorCode'] ?? 'N/A') . "\n";
echo "   Message: " . $response['message'] . "\n\n";

// Example 4: Certificate not found
echo "4. Testing certificate not found:\n";
$notFoundRequest = [
    'nit' => '99999999999999',  // NIT that doesn't have a certificate
    'passwordPri' => 'testpassword',
    'dteJson' => ['test' => 'data']
];

$response = $signer->sign($notFoundRequest);
echo "   Result: " . ($response['success'] ? '✅ Success' : '❌ Error') . "\n";
echo "   Code: " . ($response['errorCode'] ?? 'N/A') . "\n";
echo "   Message: " . $response['message'] . "\n\n";

// Example 5: Invalid JSON file
echo "5. Testing invalid JSON file:\n";
$invalidJsonFile = __DIR__ . '/invalid.json';
file_put_contents($invalidJsonFile, '{"invalid": json syntax}');  // Invalid JSON

$response = $signer->sign($invalidJsonFile);
echo "   Result: " . ($response['success'] ? '✅ Success' : '❌ Error') . "\n";
echo "   Code: " . ($response['errorCode'] ?? 'N/A') . "\n";
echo "   Message: " . $response['message'] . "\n";

// Clean up
unlink($invalidJsonFile);
echo "\n";

// Example 6: Non-existent file
echo "6. Testing non-existent file:\n";
$response = $signer->sign('/path/to/nonexistent/file.json');
echo "   Result: " . ($response['success'] ? '✅ Success' : '❌ Error') . "\n";
echo "   Code: " . ($response['errorCode'] ?? 'N/A') . "\n";
echo "   Message: " . $response['message'] . "\n\n";

// Example 7: Valid request (if certificate exists)
echo "7. Testing valid request (requires mock certificate):\n";
$validRequest = [
    'nit' => '12345678901234',
    'passwordPri' => 'testpassword',
    'dteJson' => [
        'identificacion' => [
            'tipoDte' => '01',
            'numeroControl' => 'DTE-01-TEST-001'
        ],
        'emisor' => ['nit' => '12345678901234'],
        'receptor' => ['nit' => '98765432109876'],
        'resumen' => ['totalPagar' => 100.00]
    ]
];

$response = $signer->sign($validRequest);
echo "   Result: " . ($response['success'] ? '✅ Success' : '❌ Error') . "\n";
echo "   Code: " . ($response['errorCode'] ?? 'N/A') . "\n";
echo "   Message: " . $response['message'] . "\n";

if (!$response['success'] && isset($response['errorCode']) && $response['errorCode'] === 'COD_812') {
    echo "   Note: Run 'php mock_certificate_generator.php' first to create test certificate.\n";
}

echo "\n=== Error handling examples completed ===\n";