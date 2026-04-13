<?php
// session_status.php
// Returns the current auth state for public pages

declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json; charset=UTF-8');

echo json_encode([
    'logged_in' => isset($_SESSION['user_id']),
    'user_id' => $_SESSION['user_id'] ?? null,
    'role' => $_SESSION['role'] ?? null,
    'nom' => $_SESSION['nom'] ?? null,
    'prenom' => $_SESSION['prenom'] ?? null,
]);
