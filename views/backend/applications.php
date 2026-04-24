<?php
// applications.php

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/Config.php';
require_once dirname(__DIR__) . '/partials/job_ui.php';
require_once dirname(__DIR__) . '/partials/app_header.php';
require_auth();

$userId = (int)$_SESSION['user_id'];
$userNom = (string)($_SESSION['nom'] ?? 'Utilisateur');
$userPrenom = (string)($_SESSION['prenom'] ?? '');
$role = (string)($_SESSION['role'] ?? 'artisan');
$canManageOffers = in_array($role, ['recruteur', 'entrepreneur', 'admin'], true);
$baseUrl = app_base_url();

if (!$canManageOffers) {
    http_response_code(403);
    die('Accès refusé.');
}

$idOffer = (int)($_GET['id_offer'] ?? 0);
$notice = trim((string)($_GET['notice'] ?? ''));
$search = trim((string)($_GET['q'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));
$applicationStatuses = ['pending', 'reviewed', 'shortlisted', 'interview', 'accepted', 'rejected'];
if (!in_array($statusFilter, $applicationStatuses, true)) {
    $statusFilter = '';
}

if ($idOffer <= 0) {
    header('Location: ' . $baseUrl . 'controllers/offer_emploi/offres.php');
    exit();
}

$offerStmt = $pdo->prepare(
    'SELECT o.id_offer, o.titre, o.id_recruteur, o.budget, o.duree, o.location, o.status,
            p.titre AS projet_titre,
            u.nom AS recruteur_nom, u.prenom AS recruteur_prenom
     FROM offre_emploi o
     LEFT JOIN projet p ON p.id = o.id_projet
     LEFT JOIN `user` u ON u.id_user = o.id_recruteur
     WHERE o.id_offer = ?
     LIMIT 1'
);
$offerStmt->execute([$idOffer]);
$offer = $offerStmt->fetch();

if (!$offer) {
    header('Location: ' . $baseUrl . 'controllers/offer_emploi/offres.php');
    exit();
}

if ($role !== 'admin' && (int)$offer['id_recruteur'] !== $userId) {
    http_response_code(403);
    die('Action non autorisée.');
}

if (!isset($_SESSION['offer_csrf'])) {
    $_SESSION['offer_csrf'] = bin2hex(random_bytes(16));
}
$offerCsrf = (string)$_SESSION['offer_csrf'];

$where = ['ao.id_offre = ?'];
$params = [$idOffer];
if ($search !== '') {
    $where[] = '(u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ? OR a.lettre_de_motivation LIKE ? OR a.parsed_cv_data LIKE ?)';
    $needle = "%{$search}%";
    array_push($params, $needle, $needle, $needle, $needle, $needle);
}
if ($statusFilter !== '') {
    $where[] = 'a.status = ?';
    $params[] = $statusFilter;
}

$appStmt = $pdo->prepare(
    "SELECT a.id AS id_application, a.status, a.date_creation, a.lettre_de_motivation, a.cv,
            a.parsed_cv_data, a.cv_parsing_status, a.cv_file_name, a.cv_file_size, a.cv_file_type, a.cv_file_hash,
            u.id_user, u.nom, u.prenom, u.email
     FROM application_offre ao
     JOIN application a ON a.id = ao.id_application
     JOIN `user` u ON u.id_user = a.id_user
     WHERE " . implode(' AND ', $where) . "
     ORDER BY
        CASE a.status
            WHEN 'shortlisted' THEN 1
            WHEN 'interview' THEN 2
            WHEN 'reviewed' THEN 3
            WHEN 'pending' THEN 4
            WHEN 'accepted' THEN 5
            WHEN 'rejected' THEN 6
            ELSE 7
        END,
        a.date_creation DESC"
);
$appStmt->execute($params);
$applications = $appStmt->fetchAll();

$statsStmt = $pdo->prepare(
    'SELECT a.status, COUNT(*) AS total
     FROM application_offre ao
     JOIN application a ON a.id = ao.id_application
     WHERE ao.id_offre = ?
     GROUP BY a.status'
);
$statsStmt->execute([$idOffer]);
$statusCounts = array_fill_keys($applicationStatuses, 0);
foreach ($statsStmt->fetchAll() as $row) {
    $statusCounts[(string)$row['status']] = (int)$row['total'];
}
$totalApplications = array_sum($statusCounts);
$activeApplications = $statusCounts['pending'] + $statusCounts['reviewed'] + $statusCounts['shortlisted'] + $statusCounts['interview'];
$parsedApplications = 0;
foreach ($applications as $application) {
    if (!empty($application['parsed_cv_data'])) {
        $parsedApplications++;
    }
}

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function application_status_label(string $status): string
{
    return match ($status) {
        'pending' => 'À revoir',
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
        'success' => 'CV lu automatiquement',
        'manual_entry' => 'Saisie manuelle',
        'failed' => 'Lecture échouée',
        'parsing' => 'Lecture en cours',
        default => 'En attente',
    };
}

function cv_download_label(array $app): string
{
    if (!empty($app['cv_file_name'])) {
        return (string)$app['cv_file_name'];
    }
    if (!empty($app['cv']) && preg_match('#^(?:public/)?uploads/cv/#', (string)$app['cv'])) {
        return basename((string)$app['cv']);
    }
    return trim((string)($app['cv'] ?? '')) !== '' ? 'CV texte' : 'Aucun CV';
}

function file_size_label($bytes): string
{
    $size = (int)$bytes;
    if ($size <= 0) {
        return '';
    }
    if ($size >= 1024 * 1024) {
        return number_format($size / (1024 * 1024), 1) . ' Mo';
    }
    return number_format($size / 1024, 0) . ' Ko';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Candidatures | حرفة Tunisie</title>
    <link rel="icon" type="image/x-icon" href="<?php echo h(job_asset('assets/favicon.ico')); ?>" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="<?php echo h(job_asset('assets/css/styles.css')); ?>" rel="stylesheet" />
    <style>
        <?php echo app_header_styles(); ?>
        .legacy-page-navbar { display: none !important; }
        .dashboard-navbar { background-color: rgba(59, 35, 20, 0.96) !important; padding: 0.8rem 0; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .dashboard-navbar .nav-link { color: #F5ECD7 !important; font-weight: 600; margin: 0 0.35rem; }
        .dashboard-navbar .nav-link:hover { color: #C49A6C !important; }
        .user-greeting { color: #C49A6C; font-weight: bold; margin-right: 1rem; }
        .btn-logout { background: #2E6B3E !important; color: #fff !important; padding: .45rem .9rem; border-radius: 4px; text-decoration:none; font-weight:600; }
        .hero-banner { background: linear-gradient(135deg, rgba(59, 35, 20, 0.76), rgba(46, 107, 62, 0.74)), url('<?php echo h(job_asset('assets/img/item_pics/IMG_3043.JPG')); ?>'); background-size: cover; background-position: center; color: #F5ECD7; padding: 3.2rem 0 2.6rem; border-bottom: 5px solid #8B5A3A; margin-bottom: 2rem; }
        .summary-tile { background:#fff; border:1px solid rgba(139,90,58,.16); border-left:5px solid #2E6B3E; border-radius:8px; box-shadow:0 8px 22px rgba(0,0,0,.07); padding:1rem; height:100%; }
        .summary-tile .value { color:#3B2314; font-size:1.65rem; font-weight:800; line-height:1; }
        .summary-tile .label { color:#6d6057; font-size:.86rem; margin-top:.35rem; }
        .filter-panel { background:#fff; border:1px solid rgba(139,90,58,.16); border-radius:8px; padding:1rem; box-shadow:0 8px 22px rgba(0,0,0,.06); }
        .candidate-card { background:#fff; border:1px solid rgba(139,90,58,.18); border-radius:8px; box-shadow:0 10px 24px rgba(0,0,0,.08); padding:1.15rem; }
        .candidate-grid { display:grid; grid-template-columns: minmax(240px, 0.9fr) minmax(300px, 1.2fr) minmax(260px, 0.9fr); gap:1rem; align-items:start; }
        .avatar-initials { width:52px; height:52px; border-radius:50%; display:flex; align-items:center; justify-content:center; background:#2E6B3E; color:#fff; font-weight:800; flex:0 0 auto; }
        .info-box { background:#fbfaf7; border:1px solid rgba(139,90,58,.12); border-radius:8px; padding:.8rem; }
        .profile-chip { display:inline-flex; align-items:center; gap:.35rem; background:#eef6ef; color:#244f2e; border:1px solid rgba(46,107,62,.16); border-radius:999px; padding:.25rem .55rem; font-size:.82rem; margin:.15rem .2rem .15rem 0; }
        .message-preview { max-height:130px; overflow:auto; background:#fbfaf7; border:1px solid rgba(139,90,58,.12); border-radius:8px; padding:.75rem; }
        .small-label { color:#7a6d63; font-size:.77rem; text-transform:uppercase; font-weight:800; }
        .footer-section { background:#3B2314; color:#F5ECD7; padding:3rem 0 2rem; margin-top:3rem; }
        .footer-section a { color:#F5ECD7; text-decoration:none; }
        .footer-section a:hover { color:#C49A6C; }
        @media (max-width: 1200px) { .candidate-grid { grid-template-columns: 1fr 1fr; } .actions-column { grid-column:1 / -1; } }
        @media (max-width: 768px) { .candidate-grid { grid-template-columns: 1fr; } .actions-column { grid-column:auto; } }
    </style>
</head>
<body>
<?php render_app_header('jobs'); ?>
<nav class="navbar navbar-expand-lg dashboard-navbar sticky-top legacy-page-navbar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo h($baseUrl . 'controllers/home.php'); ?>"><img src="<?php echo h(job_asset('assets/img/logo_herfa.png')); ?>" alt="Logo" height="40" style="margin-right:0.8rem;"><span style="color:#F5ECD7;font-weight:700;font-size:1.3rem;">حرفة Tunisie</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item"><a class="nav-link" href="<?php echo h($baseUrl . 'controllers/home.php'); ?>"><i class="fas fa-home me-1"></i>Accueil</a></li>
                <li class="nav-item"><a class="nav-link active" href="<?php echo h($baseUrl . 'controllers/offer_emploi/offres.php'); ?>"><i class="fas fa-briefcase me-1"></i>Emplois</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo h($baseUrl . 'controllers/user/profile.php'); ?>"><i class="fas fa-user me-1"></i>Profil</a></li>
                <li class="nav-item"><span class="user-greeting"><i class="fas fa-user-circle me-1"></i><?php echo h(trim($userPrenom . ' ' . $userNom)); ?></span></li>
                <li class="nav-item"><a class="btn-logout" href="<?php echo h($baseUrl . 'controllers/session_status.php?action=logout'); ?>"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<section class="hero-banner">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-3">
            <div>
                <h2 class="mb-1">Candidatures reçues</h2>
                <p class="mb-1">Offre : <?php echo h((string)$offer['titre']); ?></p>
                <div class="small opacity-75">
                    <?php echo h((string)($offer['projet_titre'] ?? 'Projet non lié')); ?> ·
                    <?php echo h((string)($offer['budget'] ?? '')); ?> TND ·
                    <?php echo h((string)($offer['duree'] ?? 'Durée non précisée')); ?>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?php echo h($baseUrl . 'controllers/offer_emploi/offer_details.php?id_offer=' . (int)$idOffer); ?>" class="btn btn-light"><i class="fas fa-eye me-1"></i> Voir l'offre</a>
                <a href="<?php echo h($baseUrl . 'controllers/offer_emploi/offres.php'); ?>" class="btn btn-outline-light"><i class="fas fa-arrow-left me-1"></i> Offres</a>
            </div>
        </div>
    </div>
</section>

<div class="container">
    <?php if ($notice === 'updated'): ?>
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="fas fa-check-circle"></i><div>Statut de candidature mis à jour.</div></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="summary-tile"><div class="value"><?php echo (int)$totalApplications; ?></div><div class="label">Candidatures totales</div></div></div>
        <div class="col-md-3"><div class="summary-tile"><div class="value"><?php echo (int)$activeApplications; ?></div><div class="label">À traiter</div></div></div>
        <div class="col-md-3"><div class="summary-tile"><div class="value"><?php echo (int)$statusCounts['shortlisted']; ?></div><div class="label">Présélectionnées</div></div></div>
        <div class="col-md-3"><div class="summary-tile"><div class="value"><?php echo (int)$parsedApplications; ?></div><div class="label">Profils CV affichés</div></div></div>
    </div>

    <form method="get" class="filter-panel row g-3 align-items-end mb-4">
        <input type="hidden" name="id_offer" value="<?php echo (int)$idOffer; ?>">
        <div class="col-lg-6">
            <label class="form-label">Recherche candidat</label>
            <input class="form-control" type="text" name="q" value="<?php echo h($search); ?>" placeholder="Nom, email, compétence, message...">
        </div>
        <div class="col-lg-3">
            <label class="form-label">Statut</label>
            <select class="form-select" name="status">
                <option value="">Tous les statuts</option>
                <?php foreach ($applicationStatuses as $status): ?>
                    <option value="<?php echo h($status); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>><?php echo h(application_status_label($status)); ?> (<?php echo (int)$statusCounts[$status]; ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-3 d-flex gap-2">
            <button class="btn btn-primary flex-fill" type="submit"><i class="fas fa-search me-1"></i>Filtrer</button>
            <a class="btn btn-outline-secondary" href="<?php echo h($baseUrl . 'controllers/offer_emploi/applications.php?id_offer=' . (int)$idOffer); ?>">Réinitialiser</a>
        </div>
    </form>

    <?php if (count($applications) === 0): ?>
        <div class="alert alert-light border text-center py-5">
            <h5 class="mb-2">Aucune candidature trouvée</h5>
            <p class="text-muted mb-0">Aucun candidat ne correspond aux filtres actuels.</p>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($applications as $app): ?>
                <?php
                    $fullName = trim((string)$app['prenom'] . ' ' . (string)$app['nom']);
                    $initials = mb_strtoupper(mb_substr((string)$app['prenom'], 0, 1) . mb_substr((string)$app['nom'], 0, 1));
                    $cvData = !empty($app['parsed_cv_data']) ? (json_decode((string)$app['parsed_cv_data'], true) ?: []) : [];
                    $skills = $cvData['skills'] ?? [];
                    $skills = is_array($skills) ? $skills : array_filter(array_map('trim', explode(',', (string)$skills)));
                    $cvDownloadUrl = (!empty($app['cv']) && preg_match('#^(?:public/)?uploads/cv/#', (string)$app['cv']))
                        ? $baseUrl . ltrim((string)$app['cv'], '/')
                        : '';
                    $currentStatus = (string)$app['status'];
                ?>
                <div class="col-12">
                    <article class="candidate-card">
                        <div class="candidate-grid">
                            <div>
                                <div class="d-flex gap-3 align-items-start mb-3">
                                    <div class="avatar-initials"><?php echo h($initials !== '' ? $initials : 'C'); ?></div>
                                    <div>
                                        <h5 class="mb-1"><?php echo h($fullName); ?></h5>
                                        <div class="text-muted small"><i class="fas fa-envelope me-1"></i><?php echo h((string)$app['email']); ?></div>
                                        <div class="text-muted small"><i class="fas fa-calendar me-1"></i><?php echo h((string)$app['date_creation']); ?></div>
                                    </div>
                                </div>
                                <span class="badge <?php echo job_application_status_badge_class($currentStatus); ?> px-3 py-2 mb-3"><?php echo h(application_status_label($currentStatus)); ?></span>
                                <div class="info-box">
                                    <div class="small-label mb-2">CV transmis</div>
                                    <div class="fw-bold"><?php echo h(cv_download_label($app)); ?></div>
                                    <div class="text-muted small"><?php echo h(file_size_label($app['cv_file_size'] ?? 0)); ?> <?php echo h((string)($app['cv_file_type'] ?? '')); ?></div>
                                    <div class="text-muted small mt-1"><?php echo h(parsing_status_label((string)($app['cv_parsing_status'] ?? 'pending'))); ?></div>
                                </div>
                            </div>

                            <div>
                                <div class="small-label mb-2">Profil extrait</div>
                                <div class="info-box mb-3">
                                    <div class="fw-bold"><?php echo h((string)($cvData['full_name'] ?? $fullName)); ?></div>
                                    <?php if (!empty($cvData['professional_title'])): ?><div class="text-muted small mb-2"><?php echo h((string)$cvData['professional_title']); ?></div><?php endif; ?>
                                    <?php if (!empty($cvData['phone'])): ?><div class="small"><i class="fas fa-phone me-1"></i><?php echo h((string)$cvData['phone']); ?></div><?php endif; ?>
                                    <?php if (!empty($cvData['location'])): ?><div class="small"><i class="fas fa-map-marker-alt me-1"></i><?php echo h((string)$cvData['location']); ?></div><?php endif; ?>
                                    <?php if (!empty($cvData['professional_summary'])): ?><p class="small mt-2 mb-2"><?php echo h((string)$cvData['professional_summary']); ?></p><?php endif; ?>
                                    <?php foreach (array_slice($skills, 0, 10) as $skill): ?>
                                        <span class="profile-chip"><i class="fas fa-check"></i><?php echo h((string)$skill); ?></span>
                                    <?php endforeach; ?>
                                    <?php if (empty($cvData) && empty($skills)): ?><div class="text-muted small">Aucun profil structuré disponible.</div><?php endif; ?>
                                </div>
                                <div class="small-label mb-2">Lettre de motivation</div>
                                <div class="message-preview small"><?php echo nl2br(h((string)$app['lettre_de_motivation'])); ?></div>
                            </div>

                            <div class="actions-column">
                                <form method="post" action="<?php echo h($baseUrl . 'controllers/offer_emploi/update_application.php'); ?>" class="info-box mb-3">
                                    <input type="hidden" name="id_offer" value="<?php echo (int)$idOffer; ?>">
                                    <input type="hidden" name="id_application" value="<?php echo (int)$app['id_application']; ?>">
                                    <input type="hidden" name="csrf" value="<?php echo h($offerCsrf); ?>">
                                    <label class="small-label mb-2" for="status-<?php echo (int)$app['id_application']; ?>">Décision</label>
                                    <select class="form-select mb-2" id="status-<?php echo (int)$app['id_application']; ?>" name="status">
                                        <?php foreach ($applicationStatuses as $status): ?>
                                            <option value="<?php echo h($status); ?>" <?php echo $currentStatus === $status ? 'selected' : ''; ?>><?php echo h(application_status_label($status)); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-primary w-100" type="submit"><i class="fas fa-save me-1"></i>Mettre à jour</button>
                                </form>
                                <div class="d-grid gap-2">
                                    <a class="btn btn-outline-primary" href="<?php echo h($baseUrl . 'controllers/user/profile.php?user_id=' . (int)$app['id_user']); ?>"><i class="fas fa-id-card me-1"></i>Profil candidat</a>
                                    <?php if ($cvDownloadUrl !== ''): ?>
                                        <a class="btn btn-outline-success" href="<?php echo h($cvDownloadUrl); ?>" target="_blank" rel="noopener"><i class="fas fa-file-download me-1"></i>Télécharger CV</a>
                                    <?php endif; ?>
                                    <?php if (!empty($app['email'])): ?>
                                        <a class="btn btn-outline-secondary" href="mailto:<?php echo h((string)$app['email']); ?>"><i class="fas fa-envelope me-1"></i>Contacter</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<footer class="footer-section">
    <div class="container"><div class="row"><div class="col-md-4 mb-4 mb-md-0"><img src="<?php echo h(job_asset('assets/img/logo_herfa.png')); ?>" alt="Logo" height="50" class="mb-3"><p class="small">حرفة Tunisie - La plateforme dédiée à l'artisanat tunisien et à l'entrepreneuriat responsable.</p></div><div class="col-md-2 mb-4 mb-md-0"><h6 class="mb-3">Liens rapides</h6><ul class="list-unstyled small"><li class="mb-2"><a href="<?php echo h($baseUrl . 'controllers/home.php'); ?>">Accueil</a></li><li class="mb-2"><a href="<?php echo h($baseUrl . 'controllers/projects/projets.php'); ?>">Projets</a></li><li class="mb-2"><a href="<?php echo h($baseUrl . 'controllers/formation/formations.php'); ?>">Formations</a></li><li class="mb-2"><a href="<?php echo h($baseUrl . 'controllers/offer_emploi/offres.php'); ?>">Emplois</a></li></ul></div><div class="col-md-3 mb-4 mb-md-0"><h6 class="mb-3">Ressources</h6><ul class="list-unstyled small"><li class="mb-2"><a href="#">Blog</a></li><li class="mb-2"><a href="#">FAQ</a></li><li class="mb-2"><a href="#">Support</a></li><li class="mb-2"><a href="#">Mentions légales</a></li></ul></div><div class="col-md-3"><h6 class="mb-3">Contact</h6><ul class="list-unstyled small"><li class="mb-2"><i class="fas fa-envelope me-2"></i> contact@herfa.tn</li><li class="mb-2"><i class="fas fa-phone me-2"></i> +216 70 000 000</li><li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> Tunis, Tunisie</li></ul></div></div><hr class="mt-4 mb-3" style="border-color: rgba(245,236,215,0.2);"><div class="text-center small"><p class="mb-0">© <?php echo date('Y'); ?> حرفة Tunisie - Tous droits réservés</p></div></div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo h(job_asset('assets/js/scripts.js')); ?>"></script>
</body>
</html>
