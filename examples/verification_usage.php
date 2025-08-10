<?php

declare(strict_types=1);

/**
 * Verification Usage Example
 * 
 * This example demonstrates how to verify a signed DTE and extract
 * the original JSON content from a JWS token.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Marzsv\DteSigner\DteSigner;
use Marzsv\DteSigner\DteVerifier;

echo "=== DTE Verifier - Usage Example ===\n\n";

// First, let's sign a DTE to get a JWS token for verification
echo "Step 1: Signing a DTE to get a JWS token...\n";

$signer = new DteSigner();
$signingRequest = [
    'nit' => '12345678901234',
    'passwordPri' => 'testpassword',
    'dteJson' => [
        'identificacion' => [
            'version' => 1,
            'ambiente' => '00',
            'tipoDte' => '01',
            'numeroControl' => 'DTE-01-00000001-000000000000001',
            'codigoGeneracion' => 'A1B2C3D4-E5F6-7890-1234-567890ABCDEF',
            'fecEmi' => '2025-07-20',
            'horEmi' => '10:30:00',
            'tipoMoneda' => 'USD'
        ],
        'emisor' => [
            'nit' => '12345678901234',
            'nombre' => 'EMPRESA DE EJEMPLO S.A. DE C.V.',
            'nombreComercial' => 'Empresa Ejemplo'
        ],
        'receptor' => [
            'nit' => '98765432109876',
            'nombre' => 'CLIENTE EJEMPLO S.A. DE C.V.'
        ],
        'cuerpoDocumento' => [
            [
                'numItem' => 1,
                'descripcion' => 'Servicio de consultoría',
                'cantidad' => 1,
                'precioUni' => 100.00,
                'ventaGravada' => 100.00
            ]
        ],
        'resumen' => [
            'totalGravada' => 100.00,
            'totalIva' => 13.00,
            'totalPagar' => 113.00
        ]
    ]
];

try {
    $signResponse = $signer->sign($signingRequest);
    
    if (!$signResponse['success']) {
        echo "❌ Failed to sign DTE for example:\n";
        echo "Code: " . $signResponse['errorCode'] . "\n";
        echo "Message: " . $signResponse['message'] . "\n";
        exit(1);
    }
    
    $jwsToken = $signResponse['data'];
    echo "✅ DTE signed successfully!\n";
    echo "JWS Token (first 100 chars): " . substr($jwsToken, 0, 100) . "...\n\n";
    
    // Now let's verify the signature and extract the content
    echo "Step 2: Verifying the signature and extracting content...\n";
    
    $verifier = new DteVerifier();
    
    // Verify with signature validation
    echo "\n--- Verification with signature validation ---\n";
    $verifyResponse = $verifier->verify($jwsToken, '12345678901234');
    
    if ($verifyResponse['success']) {
        echo "✅ Signature verified successfully!\n";
        echo "Message: " . $verifyResponse['message'] . "\n";
        echo "Extracted DTE data:\n";
        echo json_encode($verifyResponse['data'], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ Signature verification failed:\n";
        echo "Code: " . $verifyResponse['errorCode'] . "\n";
        echo "Message: " . $verifyResponse['message'] . "\n";
        
        if (!empty($verifyResponse['errors'])) {
            echo "Errors:\n";
            foreach ($verifyResponse['errors'] as $error) {
                echo "- " . $error . "\n";
            }
        }
    }
    
    // Extract payload without signature verification
    echo "\n--- Payload extraction without signature verification ---\n";
    $extractResponse = $verifier->extractPayload($jwsToken);
    
    if ($extractResponse['success']) {
        echo "✅ Payload extracted successfully!\n";
        echo "Message: " . $extractResponse['message'] . "\n";
        echo "⚠️  Warning: This extraction does not verify the signature!\n";
        echo "Extracted payload:\n";
        echo json_encode($extractResponse['data'], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ Payload extraction failed:\n";
        echo "Code: " . $extractResponse['errorCode'] . "\n";
        echo "Message: " . $extractResponse['message'] . "\n";
    }
    
    // Demonstrate error handling with invalid signature
    echo "\n--- Testing with invalid signature ---\n";
    $invalidJws = $jwsToken . 'invalid';
    $invalidResponse = $verifier->verify($invalidJws, '12345678901234');
    
    if (!$invalidResponse['success']) {
        echo "✅ Invalid signature correctly rejected:\n";
        echo "Message: " . $invalidResponse['message'] . "\n";
    }
    
    // Demonstrate error handling with wrong NIT
    echo "\n--- Testing with wrong NIT ---\n";
    $wrongNitResponse = $verifier->verify($jwsToken, '99999999999999');
    
    if (!$wrongNitResponse['success']) {
        echo "✅ Wrong NIT correctly rejected:\n";
        echo "Message: " . $wrongNitResponse['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Unexpected error: " . $e->getMessage() . "\n";
}

echo "\n=== Verification example completed ===\n";
echo "\nKey takeaways:\n";
echo "1. Use verify() to validate signatures and extract content safely\n";
echo "2. Use extractPayload() only when signature validation is not required\n";
echo "3. Always check the 'success' field in responses\n";
echo "4. Verification requires the NIT of the signing certificate\n";