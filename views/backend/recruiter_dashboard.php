<?php
// recruiter_dashboard.php

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/Config.php';
require_once dirname(__DIR__) . '/partials/job_ui.php';
require_once dirname(__DIR__) . '/partials/app_header.php';
require_auth();

$role = (string)($_SESSION['role'] ?? 'artisan');
if (!in_array($role, ['recruteur', 'entrepreneur', 'admin'], true)) {
    http_response_code(403);
    die('Accès refusé.');
}

$userNom = (string)($_SESSION['nom'] ?? 'Utilisateur');
$userPrenom = (string)($_SESSION['prenom'] ?? '');
$baseUrl = app_base_url();
$recruiterId = (int)$_SESSION['user_id'];
$isAdmin = $role === 'admin';
$notice = '';
$noticeType = 'success';
$notifications = [];
$unreadNotificationCount = 0;

$hasVerificationColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'verification_status'")->fetch();
$hasViewsColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'views_count'")->fetch();
$hasApplicationsColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'applications_count'")->fetch();
$hasLocationColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'location'")->fetch();
$hasCreatedAtColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'created_at'")->fetch();
$hasParsedCvDataColumn = (bool)$pdo->query("SHOW COLUMNS FROM application LIKE 'parsed_cv_data'")->fetch();
$hasCvParsingStatusColumn = (bool)$pdo->query("SHOW COLUMNS FROM application LIKE 'cv_parsing_status'")->fetch();
$hasCvFileNameColumn = (bool)$pdo->query("SHOW COLUMNS FROM application LIKE 'cv_file_name'")->fetch();
$hasCvFileSizeColumn = (bool)$pdo->query("SHOW COLUMNS FROM application LIKE 'cv_file_size'")->fetch();
$hasNotificationsTable = (bool)$pdo->query("SHOW TABLES LIKE 'notifications'")->fetch();

if (!isset($_SESSION['recruiter_dashboard_csrf'])) {
    $_SESSION['recruiter_dashboard_csrf'] = bin2hex(random_bytes(16));
}
$csrf = (string)$_SESSION['recruiter_dashboard_csrf'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedCsrf = (string)($_POST['csrf'] ?? '');
    if (!hash_equals($csrf, $postedCsrf)) {
        $notice = 'Jeton de sécurité invalide.';
        $noticeType = 'danger';
    } elseif (isset($_POST['application_id'], $_POST['status'])) {
        $applicationId = (int)$_POST['application_id'];
        $status = trim((string)$_POST['status']);
        if ($applicationId > 0 && in_array($status, ['pending', 'submitted', 'reviewed', 'shortlisted', 'interview', 'accepted', 'rejected'], true)) {
            if ($isAdmin) {
                $update = $pdo->prepare('UPDATE application SET status = ? WHERE id = ?');
                $ok = $update->execute([$status, $applicationId]);
            } else {
                $update = $pdo->prepare(
                    'UPDATE application a
                     JOIN application_offre ao ON ao.id_application = a.id
                     JOIN offre_emploi o ON o.id_offer = ao.id_offre
                     SET a.status = ?
                     WHERE a.id = ? AND o.id_recruteur = ?'
                );
                $ok = $update->execute([$status, $applicationId, $recruiterId]);
            }
            $notice = $ok ? 'Statut de candidature mis à jour.' : 'Mise à jour impossible.';
            $noticeType = $ok ? 'success' : 'danger';
        }
    } elseif (isset($_POST['offer_id'], $_POST['offer_status'])) {
        $offerId = (int)$_POST['offer_id'];
        $offerStatus = trim((string)$_POST['offer_status']);
        if ($offerId > 0 && in_array($offerStatus, ['draft', 'published', 'paused', 'closed'], true)) {
            if ($isAdmin) {
                $updateOffer = $pdo->prepare('UPDATE offre_emploi SET status = ? WHERE id_offer = ?');
                $ok = $updateOffer->execute([$offerStatus, $offerId]);
            } else {
                $updateOffer = $pdo->prepare('UPDATE offre_emploi SET status = ? WHERE id_offer = ? AND id_recruteur = ?');
                $ok = $updateOffer->execute([$offerStatus, $offerId, $recruiterId]);
            }
            $notice = $ok ? 'Statut de l’offre mis à jour.' : 'Mise à jour impossible.';
            $noticeType = $ok ? 'success' : 'danger';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hasNotificationsTable) {
    $postedCsrf = (string)($_POST['csrf'] ?? '');
    $action = trim((string)($_POST['action'] ?? ''));

    if (hash_equals($csrf, $postedCsrf) && $action === 'dismiss_notification') {
        $notificationId = (int)($_POST['notification_id'] ?? 0);
        if ($notificationId > 0) {
            $dismissStmt = $pdo->prepare('DELETE FROM notifications WHERE id = ? AND user_id = ?');
            $ok = $dismissStmt->execute([$notificationId, $recruiterId]);
            $notice = $ok ? 'Notification retiree.' : 'Suppression impossible.';
            $noticeType = $ok ? 'success' : 'danger';
        }
    } elseif (hash_equals($csrf, $postedCsrf) && $action === 'dismiss_all_notifications') {
        $dismissAllStmt = $pdo->prepare('DELETE FROM notifications WHERE user_id = ?');
        $ok = $dismissAllStmt->execute([$recruiterId]);
        $notice = $ok ? 'Toutes les notifications ont ete retirees.' : 'Suppression impossible.';
        $noticeType = $ok ? 'success' : 'danger';
    }
}

if ($hasNotificationsTable) {
    try {
        $notifCountStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM notifications
             WHERE user_id = ? AND is_read = 0"
        );
        $notifCountStmt->execute([$recruiterId]);
        $unreadNotificationCount = (int)$notifCountStmt->fetchColumn();

        $notifStmt = $pdo->prepare(
            "SELECT id, title, message, type, is_read, created_at
             FROM notifications
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT 6"
        );
        $notifStmt->execute([$recruiterId]);
        $notifications = $notifStmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log('Recruiter notifications fetch error: ' . $e->getMessage());
    }
}

$scopeWhere = $isAdmin ? '1=1' : 'o.id_recruteur = ?';
$scopeParams = $isAdmin ? [] : [$recruiterId];

$selectVerification = $hasVerificationColumn ? 'o.verification_status' : "'not_verified' AS verification_status";
$selectViews = $hasViewsColumn ? 'o.views_count' : '0 AS views_count';
$selectApplications = $hasApplicationsColumn ? 'o.applications_count' : '0 AS applications_count';
$selectLocation = $hasLocationColumn ? 'o.location' : "'' AS location";
$selectCreatedAt = $hasCreatedAtColumn ? 'o.created_at' : 'NULL AS created_at';

$offersStmt = $pdo->prepare(
    "SELECT o.id_offer, o.titre, o.budget, o.duree, o.status,
            {$selectVerification}, {$selectViews}, {$selectApplications}, {$selectLocation}, {$selectCreatedAt},
            u.nom AS recruiter_nom, u.prenom AS recruiter_prenom,
            (SELECT COUNT(*) FROM application_offre ao WHERE ao.id_offre = o.id_offer) AS actual_applications
     FROM offre_emploi o
     LEFT JOIN `user` u ON u.id_user = o.id_recruteur
     WHERE {$scopeWhere}
     ORDER BY o.id_offer DESC"
);
$offersStmt->execute($scopeParams);
$offers = $offersStmt->fetchAll();

$selectParsedCv = $hasParsedCvDataColumn ? 'a.parsed_cv_data' : 'NULL AS parsed_cv_data';
$selectParsingStatus = $hasCvParsingStatusColumn ? 'a.cv_parsing_status' : "'pending' AS cv_parsing_status";
$selectCvFileName = $hasCvFileNameColumn ? 'a.cv_file_name' : 'NULL AS cv_file_name';
$selectCvFileSize = $hasCvFileSizeColumn ? 'a.cv_file_size' : 'NULL AS cv_file_size';

$applicationsStmt = $pdo->prepare(
    "SELECT o.id_offer, o.titre AS offre_titre,
            a.id AS application_id, a.lettre_de_motivation, a.cv, a.status, a.date_creation,
            {$selectParsedCv}, {$selectParsingStatus}, {$selectCvFileName}, {$selectCvFileSize},
            candidate.id_user AS artisan_id, candidate.nom, candidate.prenom, candidate.email,
            recruiter.nom AS recruiter_nom, recruiter.prenom AS recruiter_prenom
     FROM offre_emploi o
     JOIN application_offre ao ON ao.id_offre = o.id_offer
     JOIN application a ON a.id = ao.id_application
     JOIN `user` candidate ON candidate.id_user = a.id_user
     LEFT JOIN `user` recruiter ON recruiter.id_user = o.id_recruteur
     WHERE {$scopeWhere}
     ORDER BY a.date_creation DESC, a.id DESC
     LIMIT 40"
);
$applicationsStmt->execute($scopeParams);
$applications = $applicationsStmt->fetchAll();

$statusCounts = ['pending' => 0, 'reviewed' => 0, 'shortlisted' => 0, 'interview' => 0, 'accepted' => 0, 'rejected' => 0];
foreach ($applications as $application) {
    $status = (string)$application['status'];
    $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
}
$totalOffers = count($offers);
$publishedOffers = count(array_filter($offers, static fn (array $offer): bool => in_array((string)($offer['status'] ?? ''), ['published', 'active', 'open', 'actif'], true)));
$totalApplications = count($applications);
$totalViews = array_sum(array_map(static fn (array $offer): int => (int)($offer['views_count'] ?? 0), $offers));

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function application_status_label(string $status): string
{
    return match ($status) {
        'pending', 'submitted' => 'À revoir',
        'reviewed' => 'Consultée',
        'shortlisted' => 'Présélectionnée',
        'interview' => 'Entretien',
        'accepted' => 'Acceptée',
        'rejected' => 'Refusée',
        default => ucfirst($status),
    };
}

function parsing_status_label(string $status): string
{
    return match ($status) {
        'success' => 'CV lu',
        'manual_entry' => 'Manuel',
        'failed' => 'Échec lecture',
        'parsing' => 'Lecture',
        default => 'En attente',
    };
}

function cv_label(array $application): string
{
    if (!empty($application['cv_file_name'])) {
        return (string)$application['cv_file_name'];
    }
    if (!empty($application['cv']) && preg_match('#^(?:public/)?uploads/cv/#', (string)$application['cv'])) {
        return basename((string)$application['cv']);
    }
    return trim((string)($application['cv'] ?? '')) !== '' ? 'CV texte' : 'Aucun CV';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Dashboard Recruteur | حرفة Tunisie</title>
    <link rel="icon" type="image/x-icon" href="<?php echo h(job_asset('assets/favicon.ico')); ?>" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="<?php echo h(job_asset('assets/css/styles.css')); ?>" rel="stylesheet" />
    <style>
        <?php echo app_header_styles(); ?>
        body { background:#faf7f0; }
        .hero-banner { background: linear-gradient(135deg, rgba(59,35,20,.82), rgba(46,107,62,.78)), url('<?php echo h(job_asset('assets/img/item_pics/IMG_3043.JPG')); ?>'); background-size:cover; background-position:center; color:#F5ECD7; padding:3.4rem 0 2.7rem; border-bottom:5px solid #8B5A3A; margin-bottom:2rem; }
        .metric-card { background:#fff; border:1px solid rgba(139,90,58,.16); border-left:5px solid #2E6B3E; border-radius:8px; box-shadow:0 8px 22px rgba(0,0,0,.07); padding:1rem; height:100%; }
        .metric-card .value { font-size:1.7rem; font-weight:800; color:#3B2314; line-height:1; }
        .metric-card .label { color:#6c5f55; font-size:.88rem; margin-top:.35rem; }
        .panel { background:#fff; border:1px solid rgba(139,90,58,.16); border-radius:8px; box-shadow:0 10px 24px rgba(0,0,0,.08); }
        .panel-head { padding:1rem 1.15rem; border-bottom:1px solid rgba(139,90,58,.14); display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; }
        .panel-body { padding:1.15rem; }
        .offer-row { display:grid; grid-template-columns: minmax(220px,1fr) 120px 130px 150px 220px; gap:.75rem; align-items:center; padding:.9rem 0; border-bottom:1px solid rgba(139,90,58,.10); }
        .offer-row:last-child { border-bottom:0; }
        .candidate-card { border:1px solid rgba(139,90,58,.14); border-radius:8px; padding:1rem; background:#fbfaf7; height:100%; }
        .candidate-avatar { width:44px; height:44px; border-radius:50%; background:#2E6B3E; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; flex:0 0 auto; }
        .chip { display:inline-flex; align-items:center; gap:.35rem; border-radius:999px; background:#eef6ef; color:#244f2e; border:1px solid rgba(46,107,62,.16); padding:.22rem .55rem; font-size:.78rem; margin:.12rem .14rem .12rem 0; }
        .message-preview { max-height:76px; overflow:auto; font-size:.88rem; color:#5d534b; }
        .small-label { color:#7a6d63; font-size:.76rem; font-weight:800; text-transform:uppercase; }
        .notif-list { display:flex; flex-direction:column; gap:.65rem; }
        .notif-item { background:#fff; border:1px solid rgba(139,90,58,.14); border-left:4px solid #2E6B3E; border-radius:8px; padding:.75rem .85rem; }
        .notif-item.unread { background:#f8fcf8; border-left-color:#f6c23e; }
        .notif-title { font-weight:700; color:#3B2314; margin-bottom:.2rem; }
        .notif-message { color:#5d534b; font-size:.88rem; white-space:pre-line; margin:0; }
        .notif-meta { color:#8a7d71; font-size:.76rem; margin-top:.35rem; }
        .notif-actions { display:flex; justify-content:flex-end; margin-top:.4rem; }
        .notif-dismiss-btn { border:0; background:transparent; color:#8a7d71; font-size:.78rem; font-weight:700; padding:0; }
        .notif-dismiss-btn:hover { color:#b02a37; text-decoration:underline; }
        @media (max-width: 992px) { .offer-row { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<?php render_app_header('recruiter_dashboard'); ?>

<section class="hero-banner">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-3">
            <div>
                <h1 class="mb-2"><?php echo $isAdmin ? 'Dashboard emploi admin' : 'Dashboard recruteur'; ?></h1>
                <p class="mb-0"><?php echo $isAdmin ? 'Vue globale des offres et candidatures.' : 'Pilotez vos offres, candidatures et décisions.'; ?></p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a class="btn btn-light" href="<?php echo h($baseUrl . 'controllers/offer_emploi/create_offre.php'); ?>"><i class="fas fa-plus me-1"></i>Créer offre</a>
                <a class="btn btn-outline-light" href="<?php echo h($baseUrl . 'controllers/offer_emploi/offres.php'); ?>"><i class="fas fa-briefcase me-1"></i>Voir offres</a>
            </div>
        </div>
    </div>
</section>

<main class="container">
    <?php if ($notice !== ''): ?>
        <div class="alert alert-<?php echo h($noticeType); ?>"><?php echo h($notice); ?></div>
    <?php endif; ?>

    <?php if (!empty($notifications)): ?>
        <section class="panel mb-4">
            <div class="panel-head">
                <div>
                    <h4 class="mb-0">Notifications</h4>
                    <div class="text-muted small">Messages systeme et moderation</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <?php if ($unreadNotificationCount > 0): ?>
                        <span class="badge bg-danger"><?php echo (int)$unreadNotificationCount; ?> non lue(s)</span>
                    <?php endif; ?>
                    <form method="post" class="m-0">
                        <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                        <input type="hidden" name="action" value="dismiss_all_notifications">
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Tout effacer</button>
                    </form>
                </div>
            </div>
            <div class="panel-body">
                <div class="notif-list">
                    <?php foreach ($notifications as $notif): ?>
                        <article class="notif-item <?php echo ((int)($notif['is_read'] ?? 0) === 0) ? 'unread' : ''; ?>">
                            <div class="notif-title"><?php echo h((string)($notif['title'] ?? 'Notification')); ?></div>
                            <p class="notif-message"><?php echo h((string)($notif['message'] ?? '')); ?></p>
                            <div class="notif-meta"><?php echo h((string)($notif['created_at'] ?? '')); ?></div>
                            <div class="notif-actions">
                                <form method="post" class="m-0">
                                    <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                                    <input type="hidden" name="action" value="dismiss_notification">
                                    <input type="hidden" name="notification_id" value="<?php echo (int)($notif['id'] ?? 0); ?>">
                                    <button type="submit" class="notif-dismiss-btn">Dismiss</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="metric-card"><div class="value"><?php echo (int)$totalOffers; ?></div><div class="label">Offres gérées</div></div></div>
        <div class="col-md-3"><div class="metric-card"><div class="value"><?php echo (int)$publishedOffers; ?></div><div class="label">Offres visibles</div></div></div>
        <div class="col-md-3"><div class="metric-card"><div class="value"><?php echo (int)$totalApplications; ?></div><div class="label">Candidatures reçues</div></div></div>
        <div class="col-md-3"><div class="metric-card"><div class="value"><?php echo (int)$totalViews; ?></div><div class="label">Vues cumulées</div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-xl-7">
            <section class="panel">
                <div class="panel-head">
                    <div>
                        <h4 class="mb-0">Offres</h4>
                        <div class="text-muted small"><?php echo $isAdmin ? 'Toutes les offres de la plateforme' : 'Vos offres publiées ou en préparation'; ?></div>
                    </div>
                    <a class="btn btn-sm btn-outline-primary" href="<?php echo h($baseUrl . 'controllers/offer_emploi/offres.php'); ?>">Gérer</a>
                </div>
                <div class="panel-body">
                    <?php if (!$offers): ?>
                        <div class="text-center text-muted py-4">Aucune offre pour le moment.</div>
                    <?php else: ?>
                        <?php foreach ($offers as $offer): ?>
                            <div class="offer-row">
                                <div>
                                    <div class="fw-bold"><?php echo h((string)$offer['titre']); ?></div>
                                    <div class="text-muted small"><?php echo h(trim((string)($offer['recruiter_prenom'] ?? '') . ' ' . (string)($offer['recruiter_nom'] ?? ''))); ?> <?php echo !empty($offer['location']) ? '· ' . h((string)$offer['location']) : ''; ?></div>
                                </div>
                                <div><span class="badge <?php echo h(job_offer_status_badge_class((string)($offer['status'] ?? 'draft'))); ?>"><?php echo h(job_offer_status_label((string)($offer['status'] ?? 'draft'))); ?></span></div>
                                <div class="small"><?php echo h((string)$offer['budget']); ?> TND<br><span class="text-muted"><?php echo h((string)$offer['duree']); ?></span></div>
                                <div class="small"><i class="fas fa-users me-1"></i><?php echo (int)$offer['actual_applications']; ?> candidatures<br><i class="fas fa-eye me-1"></i><?php echo (int)($offer['views_count'] ?? 0); ?> vues</div>
                                <div>
                                    <form method="post" class="d-flex gap-2">
                                        <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                                        <input type="hidden" name="offer_id" value="<?php echo (int)$offer['id_offer']; ?>">
                                        <select name="offer_status" class="form-select form-select-sm">
                                            <?php foreach (['draft' => 'Brouillon', 'published' => 'Publiée', 'paused' => 'En pause', 'closed' => 'Clôturée'] as $value => $label): ?>
                                                <option value="<?php echo h($value); ?>" <?php echo (string)$offer['status'] === $value ? 'selected' : ''; ?>><?php echo h($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-sm btn-primary" type="submit"><i class="fas fa-save"></i></button>
                                    </form>
                                    <div class="mt-2 d-flex gap-2">
                                        <a class="btn btn-sm btn-outline-secondary" href="<?php echo h($baseUrl . 'controllers/offer_emploi/offer_details.php?id_offer=' . (int)$offer['id_offer']); ?>">Détails</a>
                                        <a class="btn btn-sm btn-outline-success" href="<?php echo h($baseUrl . 'controllers/offer_emploi/applications.php?id_offer=' . (int)$offer['id_offer']); ?>">Candidats</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <div class="col-xl-5">
            <section class="panel">
                <div class="panel-head">
                    <div>
                        <h4 class="mb-0">Candidatures récentes</h4>
                        <div class="text-muted small">CV, statut et décision rapide</div>
                    </div>
                    <div class="small text-muted"><?php echo (int)$statusCounts['shortlisted']; ?> présélectionnée(s)</div>
                </div>
                <div class="panel-body">
                    <?php if (!$applications): ?>
                        <div class="text-center text-muted py-4">Aucune candidature reçue.</div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach (array_slice($applications, 0, 12) as $app): ?>
                                <?php
                                    $fullName = trim((string)$app['prenom'] . ' ' . (string)$app['nom']);
                                    $initials = mb_strtoupper(mb_substr((string)$app['prenom'], 0, 1) . mb_substr((string)$app['nom'], 0, 1)) ?: 'C';
                                    $cvData = !empty($app['parsed_cv_data']) ? (json_decode((string)$app['parsed_cv_data'], true) ?: []) : [];
                                    $skills = $cvData['skills'] ?? [];
                                    $skills = is_array($skills) ? $skills : array_filter(array_map('trim', explode(',', (string)$skills)));
                                ?>
                                <div class="col-12">
                                    <article class="candidate-card">
                                        <div class="d-flex gap-3 mb-2">
                                            <div class="candidate-avatar"><?php echo h($initials); ?></div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between gap-2">
                                                    <div>
                                                        <div class="fw-bold"><?php echo h($fullName); ?></div>
                                                        <div class="text-muted small"><?php echo h((string)$app['email']); ?></div>
                                                    </div>
                                                    <span class="badge <?php echo h(job_application_status_badge_class((string)$app['status'])); ?> align-self-start"><?php echo h(application_status_label((string)$app['status'])); ?></span>
                                                </div>
                                                <div class="text-muted small mt-1"><?php echo h((string)$app['offre_titre']); ?><?php echo $isAdmin ? ' · ' . h(trim((string)$app['recruiter_prenom'] . ' ' . (string)$app['recruiter_nom'])) : ''; ?></div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <?php foreach (array_slice($skills, 0, 5) as $skill): ?>
                                                <span class="chip"><i class="fas fa-check"></i><?php echo h((string)$skill); ?></span>
                                            <?php endforeach; ?>
                                            <?php if (!$skills): ?><span class="text-muted small">Aucune compétence extraite</span><?php endif; ?>
                                        </div>
                                        <div class="small mb-2"><strong>CV:</strong> <?php echo h(cv_label($app)); ?> · <?php echo h(parsing_status_label((string)$app['cv_parsing_status'])); ?></div>
                                        <div class="message-preview mb-3"><?php echo nl2br(h((string)$app['lettre_de_motivation'])); ?></div>
                                        <form method="post" class="d-flex gap-2">
                                            <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                                            <input type="hidden" name="application_id" value="<?php echo (int)$app['application_id']; ?>">
                                            <select class="form-select form-select-sm" name="status">
                                                <?php foreach (['pending', 'reviewed', 'shortlisted', 'interview', 'accepted', 'rejected'] as $status): ?>
                                                    <option value="<?php echo h($status); ?>" <?php echo (string)$app['status'] === $status ? 'selected' : ''; ?>><?php echo h(application_status_label($status)); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-sm btn-primary" type="submit">OK</button>
                                            <a class="btn btn-sm btn-outline-secondary" href="<?php echo h($baseUrl . 'controllers/user/profile.php?user_id=' . (int)$app['artisan_id']); ?>"><i class="fas fa-id-card"></i></a>
                                        </form>
                                    </article>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</main>

<footer class="footer-section mt-5">
    <div class="container text-center small py-4">© <?php echo date('Y'); ?> حرفة Tunisie</div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo h(job_asset('assets/js/scripts.js')); ?>"></script>
</body>
</html>
