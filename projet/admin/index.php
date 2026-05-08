<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../View/FrontOffice/login.php');
    exit;
}
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../View/FrontOffice/homepage.html');
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../Model/UserModel.php';
require_once __DIR__ . '/../Controller/AdminUserController.php';
require_once __DIR__ . '/../Controller/IdeaController.php';

$action = trim($_GET['action'] ?? 'dashboard');

switch ($action) {
    case 'dashboard':
        $userModel = new UserModel();
        $stats = [
            'total' => $userModel->count(),
            'actif' => $userModel->count(['etat_compte' => 'actif']),
            'inactif' => $userModel->count(['etat_compte' => 'inactif']),
            'byRole' => $userModel->countByRole(),
            'recent' => $userModel->findAll([], 'date_creation DESC'),
        ];
        $pageTitle = 'Tableau de bord';
        $currentPage = 'dashboard';
        require_once __DIR__ . '/../View/admin/dashboard.php';
        break;

    case 'users':
        (new AdminUserController())->index();
        break;

    case 'createUser':
        (new AdminUserController())->create();
        break;

    case 'editUser':
        (new AdminUserController())->edit();
        break;

    case 'deleteUser':
        (new AdminUserController())->delete();
        break;

    case 'ideas':
        $ideaController = new IdeaController();
        $projects = $ideaController->listProjects();
        $pageTitle = 'Gestion des idées';
        $currentPage = 'ideas';
        require_once __DIR__ . '/../View/BackOffice/index.php';
        break;

    case 'showUser':
        (new AdminUserController())->show();
        break;

    case 'toggleStatus':
        (new AdminUserController())->toggleStatus();
        break;

    case 'toggleBlock':
        (new AdminUserController())->toggleBlock();
        break;

    default:
        header('Location: index.php?action=dashboard');
        exit;
}
