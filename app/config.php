<?php
// config.php
// Central DB + session bootstrap for حرفة Tunisie

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = '127.0.0.1';
$db   = 'herfa_tunisie';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

function app_base_url(): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $appPos = strpos($scriptName, '/app/');
    if ($appPos !== false) {
        return substr($scriptName, 0, $appPos + 5);
    }
    return '/app/';
}

function app_redirect(string $path, int $statusCode = 302): void
{
    $target = ltrim($path, '/');
    header('Location: ' . app_base_url() . $target, true, $statusCode);
    exit();
}

function require_auth(): void
{
    if (!isset($_SESSION['user_id'])) {
        app_redirect('login.html');
    }
}

function require_role(string $role): void
{
    require_auth();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        http_response_code(403);
        die('Access denied.');
    }
}
