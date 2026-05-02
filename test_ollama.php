<?php
/**
 * Test Ollama Integration for CV Generator
 * Vérifie que Ollama est correctement configuré et accessible
 */

header('Content-Type: application/json; charset=utf-8');

$results = [];

// Test 1: Vérifier que cURL est disponible
$results['curl_available'] = extension_loaded('curl');

// Test 2: Tester la connexion à Ollama (force IPv4)
// Use 127.0.0.1 instead of localhost to avoid IPv6 resolution issues on Windows
$ollama_url = 'http://127.0.0.1:11434/api/generate';
$results['ollama_connection'] = false;
$results['ollama_error'] = null;

$test_payload = [
    'model' => 'mistral',
    'prompt' => 'Bonjour, es-tu un assistant professionnel?',
    'stream' => false,
    'temperature' => 0.7
];

$ch = curl_init($ollama_url);
if ($ch !== false) {
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
    // Force IPv4 to avoid localhost -> IPv6 (::1) resolution on Windows
    if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    }
    if (defined('CURLOPT_HTTP_VERSION') && defined('CURL_HTTP_VERSION_1_1')) {
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    $results['ollama_connection'] = ($http_code === 200);
    $results['http_status'] = $http_code;
    
    if ($http_code === 200 && $response) {
        $data = json_decode($response, true);
        $results['ollama_response_sample'] = isset($data['response']) 
            ? substr($data['response'], 0, 100) . '...' 
            : 'Invalid response format';
    } else {
        $results['ollama_error'] = $error ?: 'HTTP ' . $http_code;
    }
} else {
    $results['ollama_error'] = 'Impossible d\'initialiser cURL';
}

// Test 3: Vérifier les modèles disponibles
$results['available_models'] = [];
$models_url = 'http://127.0.0.1:11434/api/tags';

$ch = curl_init($models_url);
if ($ch !== false) {
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($http_code === 200 && $response) {
        $data = json_decode($response, true);
        if (isset($data['models']) && is_array($data['models'])) {
            $results['available_models'] = array_map(function($model) {
                return $model['name'] ?? 'Unknown';
            }, $data['models']);
        }
    }
}

// Test 4: Test de prompt structuré
$results['cv_generation_test'] = false;
$results['cv_test_error'] = null;

$cv_test_payload = [
    'model' => 'mistral',
    'prompt' => "Tu es un assistant CV. Génère UNIQUEMENT du JSON structuré avec ces clés:\n- resume_professionnel\n- competences_reformulees\n\nNom: Jean Dupont\nMétier: Développeur Web\n\nRéponds en JSON uniquement.",
    'stream' => false,
    'temperature' => 0.7
];

$ch = curl_init($ollama_url);
if ($ch !== false) {
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($cv_test_payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
    if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($http_code === 200 && $response) {
        $data = json_decode($response, true);
        if (isset($data['response'])) {
            // Tenter d'extraire du JSON de la réponse
            if (preg_match('/\{[\s\S]*\}/', $data['response'], $matches)) {
                $json_extracted = json_decode($matches[0], true);
                if ($json_extracted) {
                    $results['cv_generation_test'] = true;
                    $results['cv_test_sample'] = array_keys($json_extracted);
                }
            }
        }
    }
}

// Summary
$results['status'] = $results['curl_available'] && $results['ollama_connection'] ? 'ready' : 'error';
$results['timestamp'] = date('Y-m-d H:i:s');
$results['recommendation'] = '';

if (!$results['curl_available']) {
    $results['recommendation'] = 'cURL n\'est pas disponible. Vérifiez votre configuration PHP.';
} elseif (!$results['ollama_connection']) {
    $results['recommendation'] = 'Ollama n\'est pas accessible sur localhost:11434. Lancez Ollama avec: ollama serve';
} elseif (empty($results['available_models'])) {
    $results['recommendation'] = 'Aucun modèle trouvé. Téléchargez un modèle avec: ollama pull mistral';
} else {
    $results['recommendation'] = '✅ Tout est prêt! Vous pouvez utiliser le Générateur CV Intelligent.';
}

http_response_code($results['status'] === 'ready' ? 200 : 500);
echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
