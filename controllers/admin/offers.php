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
    $notice = 'Erreur de schema: ' . $e->getMessage();
    $noticeType = 'danger';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedCsrf = (string)($_POST['csrf'] ?? '');
    if (!hash_equals($csrf, $postedCsrf)) {
        $notice = 'Jeton de securite invalide.';
        $noticeType = 'danger';
    } else {
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'set_verification') {
            $offerId = (int)($_POST['offer_id'] ?? 0);
            $target = (string)($_POST['target'] ?? '');
            $moderationNote = trim((string)($_POST['moderation_note'] ?? ''));

            if ($offerId > 0 && in_array($target, ['verified', 'not_verified'], true)) {
                $ok = admin_update_offer_verification($pdo, $offerId, $target === 'verified', $adminId, $moderationNote);
                $notice = $ok ? 'Statut mis a jour.' : 'Mise a jour echouee.';
                $noticeType = $ok ? 'success' : 'danger';
            }
        } elseif ($action === 'delete_offer') {
            $offerId = (int)($_POST['offer_id'] ?? 0);
            $deleteReason = trim((string)($_POST['delete_reason'] ?? $_POST['moderation_note'] ?? ''));

            if ($offerId <= 0) {
                $notice = 'Offre invalide.';
                $noticeType = 'danger';
            } elseif ($deleteReason === '') {
                $notice = 'Veuillez fournir une raison de suppression.';
                $noticeType = 'danger';
            } else {
                $ok = admin_delete_offer_with_reason($pdo, $offerId, $adminId, $deleteReason);
                $notice = $ok
                    ? 'Offre supprimee et raison envoyee au recruteur.'
                    : 'Suppression impossible. Veuillez reessayer.';
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

$offerStats = [
    'total' => count($offers),
    'verified' => 0,
    'not_verified' => 0,
    'draft' => 0,
    'published' => 0,
    'paused' => 0,
    'closed' => 0,
];

foreach ($offers as $offer) {
    $verification = (string)($offer['verification_status'] ?? 'not_verified');
    if (isset($offerStats[$verification])) {
        $offerStats[$verification]++;
    }
    $status = (string)($offer['status'] ?? 'draft');
    if (isset($offerStats[$status])) {
        $offerStats[$status]++;
    }
}

$offerTrend = admin_build_daily_trend($offers, 'created_at', 7);

require dirname(__DIR__, 2) . '/views/backend/admin/offers.php';
