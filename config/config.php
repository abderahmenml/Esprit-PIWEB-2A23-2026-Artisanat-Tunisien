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
function get_ollama_base_url(): string
{
    // Forcer IPv4 - très important sous Windows
    return 'http://127.0.0.1:11434';
}

// Ajouter cette fonction pour les appels cURL
function init_ollama_curl($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,  // FORCER IPv4
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    return $ch;
}
function get_ollama_model(): string
{
    $m = trim((string)getenv('OLLAMA_MODEL'));
    // Utiliser un modèle plus performant comme mistral ou llama3.2
    return $m !== '' ? $m : 'mistral'; // ou 'llama3.2:3b' pour plus de pertinence
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