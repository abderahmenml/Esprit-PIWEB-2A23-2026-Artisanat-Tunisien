<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/Config.php';
require_auth();

$role = (string)($_SESSION['role'] ?? '');
if (!in_array($role, ['recruteur', 'entrepreneur', 'admin'], true)) {
    http_response_code(403);
    die('Accès refusé.');
}

app_redirect('controllers/offer_emploi/offres.php?compose=1');
