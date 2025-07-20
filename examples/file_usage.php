<?php

declare(strict_types=1);

/**
 * File Usage Example
 * 
 * This example demonstrates how to sign a DTE using a JSON file.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DteSigner\DteSigner;

echo "=== DTE Signer - File Usage Example ===\n\n";

// Initialize the signer
$signer = new DteSigner();

// Path to the sample DTE request JSON file
$requestFilePath = __DIR__ . '/sample_dte_request.json';

echo "Loading DTE request from file: " . $requestFilePath . "\n\n";

// Check if the sample file exists
if (!file_exists($requestFilePath)) {
    echo "❌ Sample request file not found!\n";
    echo "Please make sure 'sample_dte_request.json' exists in the examples directory.\n";
    exit(1);
}

try {
    // Sign the DTE using the file path
    echo "Signing DTE from file...\n";
    $response = $signer->sign($requestFilePath);
    
    if ($response['success']) {
        echo "✅ DTE signed successfully!\n\n";
        
        // Parse the original request to show some details
        $requestData = json_decode(file_get_contents($requestFilePath), true);
        echo "DTE Details:\n";
        echo "- NIT: " . $requestData['nit'] . "\n";
        echo "- Document Type: " . $requestData['dteJson']['identificacion']['tipoDte'] . "\n";
        echo "- Control Number: " . $requestData['dteJson']['identificacion']['numeroControl'] . "\n";
        echo "- Total: $" . $requestData['dteJson']['resumen']['totalPagar'] . "\n\n";
        
        echo "Signed JWS Token (first 100 chars):\n";
        echo substr($response['data'], 0, 100) . "...\n\n";
        
        // Optionally save the signed result
        $outputFile = __DIR__ . '/signed_dte_result.json';
        file_put_contents($outputFile, json_encode($response, JSON_PRETTY_PRINT));
        echo "Full response saved to: " . $outputFile . "\n";
        
    } else {
        echo "❌ Error signing DTE:\n";
        echo "Code: " . $response['errorCode'] . "\n";
        echo "Message: " . $response['message'] . "\n";
        
        if (!empty($response['errors'])) {
            echo "Validation Errors:\n";
            foreach ($response['errors'] as $error) {
                echo "- " . $error . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Unexpected error: " . $e->getMessage() . "\n";
}

echo "\n=== Example completed ===\n";