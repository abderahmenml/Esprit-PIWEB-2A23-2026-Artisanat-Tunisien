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

try {
    admin_ensure_offer_verification_schema($pdo);
} catch (Throwable $e) {
    $notice = 'Erreur de schéma: ' . $e->getMessage();
    $noticeType = 'danger';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedCsrf = (string)($_POST['csrf'] ?? '');
    if (!hash_equals($csrf, $postedCsrf)) {
        $notice = 'Jeton de sécurité invalide.';
        $noticeType = 'danger';
    } else {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'set_verification') {
            $offerId = (int)($_POST['offer_id'] ?? 0);
            $target = (string)($_POST['target'] ?? '');
            $moderationNote = trim((string)($_POST['moderation_note'] ?? ''));
            if ($offerId > 0 && in_array($target, ['verified', 'not_verified'], true)) {
                $ok = admin_update_offer_verification($pdo, $offerId, $target === 'verified', $adminId, $moderationNote);
                $notice = $ok ? 'Statut mis à jour.' : 'Mise à jour échouée.';
                $noticeType = $ok ? 'success' : 'danger';
            }
        }
    }
}

$search = trim((string)($_GET['q'] ?? ''));
$verificationFilter = trim((string)($_GET['verification'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));
$sort = trim((string)($_GET['sort'] ?? 'latest'));
$selectedOfferId = (int)($_GET['id_offer'] ?? 0);

$offers = admin_fetch_offers($pdo, $search, $verificationFilter, $statusFilter, $sort, 200);
$selectedOffer = $selectedOfferId > 0 ? admin_fetch_offer_by_id($pdo, $selectedOfferId) : null;

require dirname(__DIR__, 2) . '/views/backend/admin/offers.php';
