<?php
// Simple database config
$host = 'localhost';
$dbname = 'projet';
$username = 'root';
$password = '';

$pdo = null;
$dsn = "mysql:host=" . $host . ";dbname=" . $dbname . ";charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password);
} catch(PDOException $e) {
    echo 'Database connection failed.';
    exit;
}

if ($pdo) {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
?>