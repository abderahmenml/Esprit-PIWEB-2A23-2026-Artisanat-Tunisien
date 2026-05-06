<?php
// test_final.php - Diagnostic complet
header('Content-Type: text/plain');

echo "=== DIAGNOSTIC OLLAMA PHP ===\n\n";

// Test 1: fsockopen
echo "1. Test fsockopen: ";
$fp = @fsockopen("127.0.0.1", 11434, $errno, $errstr, 5);
if ($fp) {
    echo "✅ OK\n";
    fclose($fp);
} else {
    echo "❌ Échec: $errstr\n";
}

// Test 2: cURL avec différentes configurations
echo "\n2. Test cURL:\n";

$configs = [
    'IPv4 forcé' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
    'IPv6 forcé' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V6],
    'Auto' => []
];

foreach ($configs as $name => $opts) {
    $ch = curl_init('http://127.0.0.1:11434/api/tags');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    foreach ($opts as $opt => $val) {
        curl_setopt($ch, $opt, $val);
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "   $name: ";
    if ($httpCode === 200) {
        echo "✅ OK\n";
    } else {
        echo "❌ HTTP $httpCode, Erreur: $error\n";
    }
}

// Test 3: localhost vs 127.0.0.1
echo "\n3. Test différentes URLs:\n";
$urls = [
    'http://127.0.0.1:11434/api/tags',
    'http://localhost:11434/api/tags',
    'http://[::1]:11434/api/tags'
];

foreach ($urls as $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "   $url: " . ($httpCode === 200 ? "✅ OK\n" : "❌ HTTP $httpCode\n");
}

// Test 4: Vérifier l'environnement PHP
echo "\n4. Environnement PHP:\n";
echo "   PHP Version: " . PHP_VERSION . "\n";
echo "   cURL Version: " . (function_exists('curl_version') ? curl_version()['version'] : 'N/A') . "\n";
echo "   open_basedir: " . (ini_get('open_basedir') ?: 'aucune') . "\n";
echo "   disable_functions: " . (ini_get('disable_functions') ?: 'aucune') . "\n";