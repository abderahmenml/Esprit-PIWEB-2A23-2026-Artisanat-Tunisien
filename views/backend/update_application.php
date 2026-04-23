<?php
// update_application.php

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/Config.php';
require_auth();

$userId = (int)$_SESSION['user_id'];
$role = (string)($_SESSION['role'] ?? 'artisan');
$canManageOffers = in_array($role, ['recruteur', 'entrepreneur', 'admin'], true);
$baseUrl = app_base_url();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$canManageOffers) {
    header('Location: ' . $baseUrl . 'controllers/offer_emploi/offres.php');
    exit();
}

$postedCsrf = (string)($_POST['csrf'] ?? '');
$sessionCsrf = (string)($_SESSION['offer_csrf'] ?? '');
if ($sessionCsrf === '' || !hash_equals($sessionCsrf, $postedCsrf)) {
    header('Location: ' . $baseUrl . 'controllers/offer_emploi/offres.php');
    exit();
}

$idApplication = (int)($_POST['id_application'] ?? 0);
$idOffer = (int)($_POST['id_offer'] ?? 0);
$status = trim((string)($_POST['status'] ?? ''));

if ($idApplication <= 0 || $idOffer <= 0 || !in_array($status, ['pending', 'submitted', 'reviewed', 'shortlisted', 'interview', 'accepted', 'rejected'], true)) {
    header('Location: ' . $baseUrl . 'controllers/offer_emploi/applications.php?id_offer=' . $idOffer);
    exit();
}

$checkStmt = $pdo->prepare(
    'SELECT o.id_recruteur
     FROM application_offre ao
     JOIN offre_emploi o ON o.id_offer = ao.id_offre
     WHERE ao.id_application = ? AND ao.id_offre = ?
     LIMIT 1'
);
$checkStmt->execute([$idApplication, $idOffer]);
$row = $checkStmt->fetch();

if (!$row) {
    header('Location: ' . $baseUrl . 'controllers/offer_emploi/applications.php?id_offer=' . $idOffer);
    exit();
}

if ($role !== 'admin' && (int)$row['id_recruteur'] !== $userId) {
    http_response_code(403);
    die('Action non autorisée.');
}

$update = $pdo->prepare('UPDATE application SET status = ? WHERE id = ?');
$update->execute([$status, $idApplication]);

header('Location: ' . $baseUrl . 'controllers/offer_emploi/applications.php?id_offer=' . $idOffer . '&notice=updated');
exit();
