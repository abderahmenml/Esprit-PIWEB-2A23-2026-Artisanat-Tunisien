<?php
declare(strict_types=1);

$mode = $_GET['mode'] ?? '';

if ($mode === 'login' || $mode === '') {
    $error = $_GET['error'] ?? '';
    $target = '../login.html';
    if ($error !== '') {
        $target .= '?error=' . rawurlencode((string)$error);
    }
    header('Location: ' . $target, true, 302);
    exit();
}

header('Location: ../index.html', true, 302);
exit();
