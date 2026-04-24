<?php
session_start();

// Vérification session admin avant tout
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php?page=login');
    exit;
}
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../index.php?page=dashboard');
    exit;
}


require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../controllers/AdminUserController.php';

$action = trim($_GET['action'] ?? 'dashboard');

switch ($action) {

    // ── Dashboard ─────────────────────────────────────────────────────────
    case 'dashboard':
        $userModel = new UserModel();
        $stats = [
            'total'   => $userModel->count(),
            'actif'   => $userModel->count(['etat_compte' => 'actif']),
            'inactif' => $userModel->count(['etat_compte' => 'inactif']),
            'byRole'  => $userModel->countByRole(),
            'recent'  => $userModel->findAll([], 'date_creation DESC'),
        ];
        $pageTitle   = 'Tableau de bord';
        $currentPage = 'dashboard';
        require_once __DIR__ . '/../views/admin/dashboard.php';
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

    case 'showUser':
        (new AdminUserController())->show();
        break;

    case 'toggleStatus':
        (new AdminUserController())->toggleStatus();
        break;

    case 'toggleBlock':
        (new AdminUserController())->toggleBlock();
        break;

    // ── Default ───────────────────────────────────────────────────────────
    default:
        header('Location: index.php?action=dashboard');
        exit;
}
