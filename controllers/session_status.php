<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/Config.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (($_GET['action'] ?? '') === 'logout') {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
    }

    session_destroy();
    header('Location: /herfa/public/frontend/index.html', true, 302);
    exit();
}

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'logged_in' => isset($_SESSION['user_id']),
    'user_id' => isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null,
    'role' => isset($_SESSION['role']) ? (string)$_SESSION['role'] : null,
    'nom' => isset($_SESSION['nom']) ? (string)$_SESSION['nom'] : null,
    'prenom' => isset($_SESSION['prenom']) ? (string)$_SESSION['prenom'] : null,
], JSON_UNESCAPED_UNICODE);
