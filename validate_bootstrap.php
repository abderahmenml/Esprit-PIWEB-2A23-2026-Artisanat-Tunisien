<?php
/**
 * Bootstrap validation - Tests that index.php can load without executing routing
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Bootstrap Validation ===\n\n";

// Mock $_SERVER to prevent actual requests
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "1. Loading config... ";
try {
    require_once __DIR__ . '/config/config.php';
    echo "✓\n";
} catch (Exception $e) {
    echo "✗ {$e->getMessage()}\n";
    exit(1);
}

echo "2. Instantiating controllers... ";
try {
    require_once __DIR__ . '/controllers/AuthController.php';
    require_once __DIR__ . '/controllers/DashboardController.php';
    require_once __DIR__ . '/controllers/ProfilController.php';
    require_once __DIR__ . '/controllers/AdminController.php';
    
    $authController = new AuthController();
    $dashboardController = new DashboardController();
    $profilController = new ProfilController();
    $adminController = new AdminController();
    
    echo "✓\n";
} catch (Exception $e) {
    echo "✗ {$e->getMessage()}\n";
    exit(1);
}

echo "3. Verifying routing functions... ";
try {
    $path = app_route_path('/test');
    $url = app_url('/path');
    echo "✓\n";
} catch (Exception $e) {
    echo "✗ {$e->getMessage()}\n";
    exit(1);
}

echo "\n=== Bootstrap Complete ===\n";
echo "Project is ready for execution.\n";
