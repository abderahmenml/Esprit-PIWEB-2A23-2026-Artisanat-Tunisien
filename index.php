<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/controllers/ProfilController.php';
require_once __DIR__ . '/controllers/AdminController.php';

$uriPath = app_route_path($_SERVER['REQUEST_URI'] ?? '/');
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($uriPath === '/admin.php') {
    $uriPath = '/admin';
}

$authController = new AuthController();
$dashboardController = new DashboardController();
$profilController = new ProfilController();
$adminController = new AdminController();

if ($uriPath === '/') {
    if (isset($_SESSION['user_id'])) {
        header('Location: ' . app_url('/dashboard'));
    } else {
        header('Location: ' . app_url('/auth/login'));
    }
    exit;
}

$notFound = false;
$methodNotAllowed = false;

switch ($uriPath) {
    case '/auth':
    case '/auth/login':
        if ($method === 'GET' || $method === 'POST') {
            $authController->login();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/auth/register':
        if ($method === 'GET' || $method === 'POST') {
            $authController->register();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/auth/logout':
    case '/logout':
        if ($method === 'GET') {
            $authController->logout();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/dashboard':
        if ($method === 'GET') {
            $dashboardController->index();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/dashboard/annuaire':
        if ($method === 'GET') {
            $dashboardController->annuaire();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/profil':
        if ($method === 'GET') {
            $profilController->index();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/profil/gestion_competences':
        if ($method === 'GET') {
            $profilController->gestion_competences();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/profil/gestion_certifications':
        if ($method === 'GET') {
            $profilController->gestion_certifications();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/profil/update':
        if ($method === 'POST') {
            $profilController->update();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/profil/addBio':
        if ($method === 'POST') {
            $profilController->addBio();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/profil/updateBio':
        if ($method === 'POST') {
            $profilController->updateBio();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/profil/deleteBio':
        if ($method === 'POST') {
            $profilController->deleteBio();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/profil/addCompetence':
        if ($method === 'POST') {
            $profilController->addCompetence();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/profil/updateCompetence':
        if ($method === 'POST') {
            $profilController->updateCompetence();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/profil/deleteCompetence':
        if ($method === 'POST') {
            $profilController->deleteCompetence();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/profil/saveCompetences':
        if ($method === 'POST') {
            $profilController->saveCompetences();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/profil/recalculateCompletion':
        if ($method === 'POST') {
            $profilController->recalculateCompletion();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/profil/addCertification':
        if ($method === 'POST') {
            $profilController->addCertification();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/profil/updateCertification':
        if ($method === 'POST') {
            $profilController->updateCertification();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/profil/deleteCertification':
        if ($method === 'POST') {
            $profilController->deleteCertification();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/profil/addExperience':
        if ($method === 'POST') {
            $profilController->addExperience();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/profil/updateExperience':
        if ($method === 'POST') {
            $profilController->updateExperience();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/profil/deleteExperience':
        if ($method === 'POST') {
            $profilController->deleteExperience();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/profil/addPortfolioFile':
        if ($method === 'POST') {
            $profilController->addPortfolioFile();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/profil/upsertMetierAvance':
        if ($method === 'POST') {
            $profilController->upsertMetierAvance();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/profil/deleteMetierAvance':
        if ($method === 'POST') {
            $profilController->deleteMetierAvance();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/profil/analyseMetierAvance':
        if ($method === 'POST') {
            $profilController->analyseMetierAvance();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/profil/generateCvAi':
        if ($method === 'POST') {
            $profilController->generateCvAi();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/profil/generatePpeiAi':
        if ($method === 'POST') {
            $profilController->generatePpeiAi();
        } else {
            $methodNotAllowed = true;
        }
        break;

case '/profil/chatbot':
    if ($method === 'POST') {
        $profilController->chatbot();
    } else {
        $methodNotAllowed = true;
    }
    break;

    case '/profil/generateBioFromText':
        if ($method === 'POST') {
            $profilController->generateBioFromText();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/profil/optimizeCvAi':
        if ($method === 'POST') {
            $profilController->optimizeCvAi();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/admin':
        if ($method === 'GET') {
            $adminController->index();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/admin/competences':
        if ($method === 'GET') {
            $adminController->competences();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/admin/addUser':
        if ($method === 'POST') {
            $adminController->addUser();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/admin/updateUser':
        if ($method === 'POST') {
            $adminController->updateUser();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/admin/addCompetence':
        if ($method === 'POST') {
            $adminController->addCompetence();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/admin/updateCompetence':
        if ($method === 'POST') {
            $adminController->updateCompetence();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/admin/deleteCompetence':
        if ($method === 'POST') {
            $adminController->deleteCompetence();
        } else {
            $methodNotAllowed = true;
        }
        break;

    case '/admin/deleteUser':
        if ($method === 'POST') {
            $adminController->deleteUser();
        } else {
            $methodNotAllowed = true;
        }
        break;

    default:
        $notFound = true;
        break;
}

if ($methodNotAllowed) {
    http_response_code(405);
    echo '405 - Methode non autorisee';
    exit;
}

if ($notFound) {
    http_response_code(404);
    echo '404 - Page non trouvee';
    exit;
}