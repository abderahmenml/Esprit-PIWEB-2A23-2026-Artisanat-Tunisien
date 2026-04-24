<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$adminId = (int)($_SESSION['user_id'] ?? 0);
$adminName = trim(((string)($_SESSION['prenom'] ?? '')) . ' ' . ((string)($_SESSION['nom'] ?? 'Admin')));

if (!isset($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(16));
}
$csrf = (string)$_SESSION['admin_csrf'];

$notice = '';
$noticeType = 'success';
$schemaReady = true;

try {
    admin_ensure_offer_verification_schema($pdo);
} catch (Throwable $e) {
    $schemaReady = false;
    $notice = 'Le schéma de vérification n\'a pas pu être initialisé: ' . $e->getMessage();
    $noticeType = 'danger';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $schemaReady) {
    $action = (string)($_POST['action'] ?? '');
    $postedCsrf = (string)($_POST['csrf'] ?? '');

    if (!hash_equals($csrf, $postedCsrf)) {
        $notice = 'Jeton de sécurité invalide.';
        $noticeType = 'danger';
    } elseif ($action === 'set_verification') {
        $offerId = (int)($_POST['offer_id'] ?? 0);
        $target = (string)($_POST['target'] ?? '');
        $verified = $target === 'verified';

        if ($offerId > 0 && in_array($target, ['verified', 'not_verified'], true)) {
            $ok = admin_update_offer_verification($pdo, $offerId, $verified, $adminId);
            $notice = $ok ? 'Statut de vérification mis à jour.' : 'Échec de mise à jour.';
            $noticeType = $ok ? 'success' : 'danger';
        }
    }
}

$search = trim((string)($_GET['q'] ?? ''));
$verificationFilter = trim((string)($_GET['verification'] ?? ''));
$sort = trim((string)($_GET['sort'] ?? 'latest'));

$stats = $schemaReady
    ? admin_get_offer_dashboard_stats($pdo)
    : ['total_offers' => 0, 'verified_offers' => 0, 'not_verified_offers' => 0, 'active_recruiters' => 0];

$offers = $schemaReady ? admin_fetch_offers($pdo, $search, $verificationFilter, '', $sort, 12) : [];
$latestApplications = $schemaReady ? admin_fetch_latest_applications($pdo, 8) : [];

require dirname(__DIR__, 2) . '/views/backend/admin/dashboard.php';
