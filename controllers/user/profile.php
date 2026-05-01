<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/Config.php';
require_once dirname(__DIR__, 2) . '/models/Database.php';

function getPDO(): PDO
{
    return Database::getPDO();
}

function app_url(string $path = ''): string
{
    $path = trim($path);
    $base = rtrim(app_base_url(), '/');

    if ($path === '' || $path === '/') {
        return $base . '/controllers/user/profile.php';
    }

    if (str_starts_with($path, '/profil')) {
        $suffix = trim(substr($path, strlen('/profil')), '/');
        $target = $base . '/controllers/user/profile.php';

        if ($suffix !== '') {
            $target .= '?action=' . rawurlencode($suffix);
        }

        return $target;
    }

    if ($path === '/dashboard') {
        return $base . '/controllers/home.php';
    }

    if ($path === '/dashboard/annuaire') {
        return $base . '/controllers/home.php';
    }

    if ($path === '/admin') {
        return $base . '/controllers/admin/dashboard.php';
    }

    if ($path === '/auth/logout') {
        return $base . '/controllers/session_status.php?action=logout';
    }

    if ($path === '/auth/login') {
        return $base . '/public/frontend/login.html';
    }

    return $base . '/' . ltrim($path, '/');
}

require_once __DIR__ . '/profil/ProfilModel.php';
require_once __DIR__ . '/profil/ProfilController.php';

$controller = new ProfilController();
$action = trim((string)($_GET['action'] ?? ''));
$action = $action === '' ? 'index' : $action;

$allowed = [
    'index',
    'gestion_competences',
    'gestion_certifications',
    'addCompetence',
    'deleteCompetence',
    'updateCompetence',
    'addExperience',
    'deleteExperience',
    'updateExperience',
    'addCertification',
    'deleteCertification',
    'updateCertification',
    'update',
    'addBio',
    'updateBio',
    'deleteBio',
    'upsertMetierAvance',
    'deleteMetierAvance',
    'analyseMetierAvance',
    'addPortfolioFile',
];

if (!in_array($action, $allowed, true) || !method_exists($controller, $action)) {
    http_response_code(404);
    echo 'Action introuvable.';
    exit;
}

$controller->{$action}();
