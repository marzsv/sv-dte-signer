<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Marzsv\DteSigner\Cache\InMemoryCache;
use Marzsv\DteSigner\Builders\DteSignerFactory;

$certificateDir = __DIR__ . '/certificates';

$cache = new InMemoryCache();

$signer = DteSignerFactory::forDirectory($certificateDir)
    ->withCache($cache)
    ->buildSigner();

$verifier = DteSignerFactory::forDirectory($certificateDir)
    ->withCache($cache)
    ->buildVerifier();

$dteData = [
    'nit' => '12345678-9',
    'privateKeyPassword' => 'password123',
    'dteJson' => [
        'version' => '1.0',
        'codigo' => 'DTE-001',
        'fecha' => date('Y-m-d'),
        'monto' => 100.00,
    ]
];

echo "=== Signing DTE ===\n";
$signResult = $signer->sign($dteData);

if ($signResult['success']) {
    $jws = $signResult['data'];
    echo "✓ DTE signed successfully\n";
    echo "JWS Token: " . substr($jws, 0, 50) . "...\n\n";

    echo "=== Verifying DTE (with cache) ===\n";
    $verifyResult = $verifier->verify($jws, '12345678-9');

    if ($verifyResult['success']) {
        echo "✓ DTE verified successfully\n";
        echo "Original payload:\n";
        print_r($verifyResult['data']);
    } else {
        echo "✗ Verification failed: " . $verifyResult['message'] . "\n";
    }
} else {
    echo "✗ Signing failed: " . $signResult['message'] . "\n";
    print_r($signResult);
}

echo "\n=== Cache Benefits ===\n";
echo "- Certificate files are read only once\n";
echo "- XML parsing happens only once\n";
echo "- Public key extraction (expensive OpenSSL operations) happens only once\n";
echo "- In-memory cache reuses these expensive results across operations\n";
echo "- Ideal for batch operations on the same NITs\n";
