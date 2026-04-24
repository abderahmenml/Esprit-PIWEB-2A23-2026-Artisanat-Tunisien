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
require_once __DIR__ . '/controllers/ProfileController.php';
require_once __DIR__ . '/controllers/TestSyncController.php';
require_once __DIR__ . '/models/CategorieModel.php';
require_once __DIR__ . '/models/ProjetModel.php';
require_once __DIR__ . '/controllers/ProjetController.php';

$page = trim($_GET['page'] ?? 'login');

$auth = new AuthController();

switch ($page) {
    case 'login':
        $auth->login();
        break;

    case 'login_face':
        $auth->loginFace();
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

    case 'reset_success':
        $auth->resetSuccess();
        break;

    case 'verify':
        $auth->verify();
        break;

    case 'test_sync':
        $testSyncCtrl = new TestSyncController();
        $testSyncCtrl->index();
        break;

    case 'dashboard':
        // Vérification de session
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }
        require_once __DIR__ . '/views/auth/dashboard.php';
        break;

    // ── GESTION DES PROJETS ───────────────────────────────────────────────────
    case 'projets':
        $projetCtrl = new ProjetController();
        $projetCtrl->index();
        break;

    case 'projets_recherche':
        $projetCtrl = new ProjetController();
        $projetCtrl->rechercherParCategorie();
        break;

    case 'projets_ajouter':
        $projetCtrl = new ProjetController();
        $projetCtrl->ajouter();
        break;

    case 'projets_show':
        $projetCtrl = new ProjetController();
        $projetCtrl->show();
        break;

    case 'projets_modifier':
        $projetCtrl = new ProjetController();
        $projetCtrl->modifier();
        break;

    case 'projets_supprimer':
        $projetCtrl = new ProjetController();
        $projetCtrl->supprimer();
        break;

    case 'profile':
        $profileCtrl = new ProfileController();
        $method = trim($_GET['method'] ?? 'index');
        if (method_exists($profileCtrl, $method)) {
            $profileCtrl->$method();
        } else {
            $profileCtrl->index();
        }
        break;

    default:
        header('Location: index.php?page=login');
        exit;
}
