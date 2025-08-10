<?php

declare(strict_types=1);

/**
 * Basic Usage Example
 * 
 * This example demonstrates how to sign a DTE using direct JSON data.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Marzsv\DteSigner\DteSigner;

echo "=== DTE Signer - Basic Usage Example ===\n\n";

// Initialize the signer with the default certificate directory
$signer = new DteSigner();

// Example DTE signing request
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

echo "Signing DTE with data:\n";
echo "- NIT: " . $signingRequest['nit'] . "\n";
echo "- DTE Type: Factura\n";
echo "- Total: $113.00\n\n";

try {
    // Sign the DTE
    $response = $signer->sign($signingRequest);
    
    if ($response['success']) {
        echo "✅ DTE signed successfully!\n\n";
        echo "Signed JWS Token:\n";
        echo substr($response['data'], 0, 100) . "...\n\n";
        echo "Full response:\n";
        echo json_encode($response, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ Error signing DTE:\n";
        echo "Code: " . $response['errorCode'] . "\n";
        echo "Message: " . $response['message'] . "\n";
        
        if (!empty($response['errors'])) {
            echo "Errors:\n";
            foreach ($response['errors'] as $error) {
                echo "- " . $error . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Unexpected error: " . $e->getMessage() . "\n";
}

echo "\n=== Example completed ===\n";