<?php
/**
 * Quick setup validation script
 * Runs syntax and include checks
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Project Setup Validation ===\n\n";

// 1. Check config
echo "1. Checking config/config.php... ";
try {
    require_once __DIR__ . '/config/config.php';
    echo "✓ OK\n";
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Check controllers
$controllers = [
    'AuthController',
    'DashboardController',
    'ProfilController',
    'AdminController'
];

echo "\n2. Checking controllers:\n";
foreach ($controllers as $ctrl) {
    echo "   - $ctrl... ";
    try {
        require_once __DIR__ . "/controllers/{$ctrl}.php";
        echo "✓ OK\n";
    } catch (Exception $e) {
        echo "✗ FAILED: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// 3. Check ProfilModel
echo "\n3. Checking models... ";
try {
    require_once __DIR__ . '/models/ProfilModel.php';
    echo "✓ OK\n";
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

// 4. Check API
echo "\n4. Checking API... ";
try {
    require_once __DIR__ . '/api/chatbot.php';
    echo "✓ OK (note: chatbot may need environment vars)\n";
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== All checks passed! ===\n";
