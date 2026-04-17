<?php
class Config {
    private static $pdo = null;
    
    public static function getConnexion() {
        if (self::$pdo === null) {
            $servername = "localhost";
            $username = "root";
            $password = "";
            $dbname = "projet";
            
            try {
                self::$pdo = new PDO(
                    "mysql:host=$servername;dbname=$dbname;charset=utf8",
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
            } catch (PDOException $e) {
                die("Erreur de connexion: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}

function getPDO() {
    return Config::getConnexion();
}

function app_base_path(): string {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = rtrim(dirname($scriptName), '/');

    if ($base === '.' || $base === '/') {
        return '';
    }

    return $base;
}

function app_url(string $path = '/'): string {
    $base = app_base_path();

    if ($path === '') {
        $path = '/';
    }

    if ($path[0] !== '/') {
        $path = '/' . $path;
    }

    if ($base === '') {
        return $path;
    }

    return $base . $path;
}

function app_route_path(?string $requestUri): string {
    $path = parse_url($requestUri ?? '/', PHP_URL_PATH);

    if (!is_string($path) || $path === '') {
        return '/';
    }

    if ($path[0] !== '/') {
        $path = '/' . $path;
    }

    $base = app_base_path();
    if ($base !== '' && strpos($path, $base) === 0) {
        $path = substr($path, strlen($base));
        if ($path === false || $path === '') {
            $path = '/';
        }
    }

    if ($path !== '/' && substr($path, -1) === '/') {
        $path = rtrim($path, '/');
    }

    return $path;
}