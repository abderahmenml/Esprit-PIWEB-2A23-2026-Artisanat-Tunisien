<?php
// config.php
// Central DB + session bootstrap for حرفة Tunisie

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

spl_autoload_register(static function (string $class): void {
    $baseDir = dirname(__DIR__);
    $candidates = [
        $baseDir . '/models/' . $class . '.php',
        $baseDir . '/services/' . $class . '.php',
    ];

    foreach ($candidates as $file) {
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

$envPath = dirname(__DIR__) . '/.env';
if (is_file($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            [$k, $v] = $parts;
            $k = trim($k);
            $v = trim($v);
            putenv("{$k}={$v}");
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
        }
    }
}

function env(string $key, $default = null)
{
    $val = getenv($key);
    return $val === false ? $default : $val;
}

$host = env('DB_HOST', '127.0.0.1');
$db   = env('DB_NAME', 'herfa_tunisie');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', '');
$charset = env('DB_CHARSET', 'utf8mb4');

// OpenAI API key (load from environment)
$OPENAI_API_KEY = env('OPENAI_API_KEY', null);

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
    $rootPos = strpos($scriptName, '/herfa/');
    if ($rootPos !== false) {
        return '/herfa/';
    }

    $parts = array_values(array_filter(explode('/', trim($scriptName, '/'))));
    if (!empty($parts)) {
        return '/' . $parts[0] . '/';
    }

    return '/';
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
        app_redirect('public/frontend/login.html');
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
