<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'logged_in' => isset($_SESSION['user_id']),
    'user_id' => isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null,
    'role' => isset($_SESSION['role']) ? (string)$_SESSION['role'] : null,
    'nom' => isset($_SESSION['nom']) ? (string)$_SESSION['nom'] : null,
    'prenom' => isset($_SESSION['prenom']) ? (string)$_SESSION['prenom'] : null,
], JSON_UNESCAPED_UNICODE);
