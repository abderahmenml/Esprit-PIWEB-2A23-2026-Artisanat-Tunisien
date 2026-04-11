<?php
$host = "localhost";
$dbname = "craftlink_db";
$user = "root";
$password = ""; // vide par défaut dans XAMPP

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["success" => false, "message" => "Connexion échouée : " . $e->getMessage()]));
}
?>