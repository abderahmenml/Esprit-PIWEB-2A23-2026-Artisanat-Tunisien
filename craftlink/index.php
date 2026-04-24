<?php
/**
 * index.php — Point d'entrée principal (FrontOffice)
 * Routeur simple basé sur le paramètre GET ?page=
 */

session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Model.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/core/Validator.php';
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/models/PendingUserModel.php';
require_once __DIR__ . '/models/PasswordResetModel.php';
require_once __DIR__ . '/controllers/AuthController.php';

$page = trim($_GET['page'] ?? 'login');

$auth = new AuthController();

switch ($page) {
    case 'login':
        $auth->login();
        break;

    case 'register':
        $auth->register();
        break;

    case 'logout':
        $auth->logout();
        break;

    case 'forgot_password':
        $auth->forgotPassword();
        break;

    case 'verify':
        $auth->verify();
        break;

    case 'dashboard':
        // Vérification de session
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }
        require_once __DIR__ . '/views/auth/dashboard.php';
        break;

    default:
        header('Location: index.php?page=login');
        exit;
}
