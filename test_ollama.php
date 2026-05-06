<?php
// test_curl_fixed.php
$payload = [
    'model' => 'mistral',
    'prompt' => 'Dis "OK"',
    'stream' => false,
    'options' => ['num_predict' => 10]
];

$jsonPayload = json_encode($payload);
$tempFile = sys_get_temp_dir() . '/test_ollama.json';
file_put_contents($tempFile, $jsonPayload);

$command = 'curl -s -X POST http://127.0.0.1:11434/api/generate -H "Content-Type: application/json" -d @' . escapeshellarg($tempFile) . ' 2>&1';
echo "Commande: $command\n\n";

$output = shell_exec($command);
unlink($tempFile);

echo "Résultat brut: $output\n\n";

if ($output) {
    $result = json_decode($output, true);
    echo "Réponse: " . ($result['response'] ?? 'erreur');
}