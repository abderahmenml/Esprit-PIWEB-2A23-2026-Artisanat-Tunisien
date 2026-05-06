<?php
// test_ollama_generate.php
header('Content-Type: text/plain');

echo "=== TEST GÉNÉRATION OLLAMA ===\n\n";

$testPrompts = [
    'Test 1: Bonjour simple' => 'Dis simplement "OLLAMA_OK"',
    'Test 2: Bio courte' => 'Écris une phrase professionnelle pour un artisan'
];

foreach ($testPrompts as $name => $prompt) {
    echo "$name...\n";
    
    $payload = [
        'model' => 'mistral',
        'prompt' => $prompt,
        'stream' => false,
        'options' => [
            'num_predict' => 50
        ]
    ];

    $ch = curl_init('http://127.0.0.1:11434/api/generate');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    ]);

    $start = microtime(true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $time = round((microtime(true) - $start) * 1000);
    curl_close($ch);

    if ($response && $httpCode === 200) {
        $data = json_decode($response, true);
        $reply = $data['response'] ?? '';
        echo "   ✅ Réponse (" . strlen($reply) . " chars) en {$time}ms\n";
        echo "   -> " . substr($reply, 0, 100) . "\n\n";
    } else {
        echo "   ❌ Échec: HTTP $httpCode, Error: $error\n\n";
    }
}

echo "\n=== RÉSULTAT FINAL ===\n";
echo "Si les tests passent, Ollama fonctionne correctement.\n";
echo "Si les tests échouent, redémarrez Ollama avec: ollama serve\n";