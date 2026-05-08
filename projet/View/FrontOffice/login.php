<?php
require_once '../../config.php';
require_once '../../Controller/AuthController.php';

$controller = new AuthController();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'login_face') {
        $controller->loginFace();
        return;
    }
}

$controller->login();
