<?php
// update_application.php

declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_auth();

$userId = (int)$_SESSION['user_id'];
$role = (string)($_SESSION['role'] ?? 'artisan');
$canManageOffers = in_array($role, ['recruteur', 'entrepreneur', 'admin'], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$canManageOffers) {
    header('Location: offres.php');
    exit();
}

$idApplication = (int)($_POST['id_application'] ?? 0);
$idOffer = (int)($_POST['id_offer'] ?? 0);
$status = trim((string)($_POST['status'] ?? ''));

if ($idApplication <= 0 || $idOffer <= 0 || !in_array($status, ['pending', 'accepted', 'rejected'], true)) {
    header('Location: applications.php?id_offer=' . $idOffer);
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
    header('Location: applications.php?id_offer=' . $idOffer);
    exit();
}

if ($role !== 'admin' && (int)$row['id_recruteur'] !== $userId) {
    http_response_code(403);
    die('Action non autorisée.');
}

$update = $pdo->prepare('UPDATE application SET status = ? WHERE id = ?');
$update->execute([$status, $idApplication]);

header('Location: applications.php?id_offer=' . $idOffer . '&notice=updated');
exit();
