<?php
// diagnostic_ollama.php - Diagnostic complet
session_start();
require_once 'config/config.php';

echo "<!DOCTYPE html>
<html>
<head><title>Diagnostic Ollama</title><style>body{font-family:monospace;padding:20px;}.ok{color:green;}.error{color:red;}.info{color:blue;}</style></head>
<body>
<h1>🔍 Diagnostic Ollama</h1>";

// Test 1: Configuration
echo "<h2>1. Configuration</h2>";
echo "<p>OLLAMA_BASE_URL: " . get_ollama_base_url() . "</p>";
echo "<p>OLLAMA_MODEL: " . get_ollama_model() . "</p>";

// Test 2: Connexion réseau
echo "<h2>2. Test connexion réseau</h2>";
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
    
    $status = ($httpCode === 200) ? "<span class='ok'>✅ OK</span>" : "<span class='error'>❌ Échec</span>";
    echo "<p>$url : $status (HTTP $httpCode)</p>";
}

// Test 3: Modèles installés
echo "<h2>3. Modèles installés</h2>";
$ch = curl_init('http://127.0.0.1:11434/api/tags');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
$response = curl_exec($ch);
curl_close($ch);

if ($response) {
    $data = json_decode($response, true);
    if (isset($data['models'])) {
        echo "<ul>";
        foreach ($data['models'] as $model) {
            echo "<li>" . $model['name'] . "</li>";
        }
        echo "</ul>";
        
        // Vérifier si mistral est présent
        $hasMistral = false;
        foreach ($data['models'] as $model) {
            if (strpos($model['name'], 'mistral') !== false) {
                $hasMistral = true;
                break;
            }
        }
        
        if (!$hasMistral) {
            echo "<p class='error'>⚠️ Mistral n'est pas installé. Exécutez: <code>ollama pull mistral</code></p>";
        }
    }
}

// Test 4: Génération réelle
echo "<h2>4. Test de génération</h2>";
$testPayload = [
    'model' => 'mistral',
    'messages' => [['role' => 'user', 'content' => 'Dis simplement "TEST_OK"']],
    'stream' => false
];

$ch = curl_init('http://127.0.0.1:11434/api/chat');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testPayload));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

$start = microtime(true);
$response = curl_exec($ch);
$time = round((microtime(true) - $start) * 1000);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($response && $httpCode === 200) {
    $data = json_decode($response, true);
    $reply = $data['message']['content'] ?? '';
    echo "<p class='ok'>✅ Génération réussie en {$time}ms</p>";
    echo "<p>Réponse: <strong>$reply</strong></p>";
} else {
    echo "<p class='error'>❌ Échec de génération</p>";
    echo "<p>HTTP Code: $httpCode</p>";
    echo "<p>cURL Error: $error</p>";
}

// Test 5: Test via votre API
echo "<h2>5. Test via votre API</h2>";
$testUrl = app_url('/profil/generatePpeiAi');
echo "<p>Test URL: $testUrl</p>";

echo "</body></html>";