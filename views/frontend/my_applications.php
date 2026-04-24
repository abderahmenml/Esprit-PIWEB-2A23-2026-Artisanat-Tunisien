<?php
// my_applications.php

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/Config.php';
require_once dirname(__DIR__) . '/partials/job_ui.php';
require_once dirname(__DIR__) . '/partials/app_header.php';
require_role('artisan');

$userId = (int)$_SESSION['user_id'];
$userNom = (string)($_SESSION['nom'] ?? 'Utilisateur');
$userPrenom = (string)($_SESSION['prenom'] ?? '');
$baseUrl = app_base_url();
$notice = trim((string)($_GET['notice'] ?? ''));
$search = trim((string)($_GET['q'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));
$allowedStatuses = ['pending', 'reviewed', 'shortlisted', 'accepted', 'rejected'];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

$hasParsedCvDataColumn = (bool)$pdo->query("SHOW COLUMNS FROM application LIKE 'parsed_cv_data'")->fetch();
$hasCvParsingStatusColumn = (bool)$pdo->query("SHOW COLUMNS FROM application LIKE 'cv_parsing_status'")->fetch();
$hasCvFileNameColumn = (bool)$pdo->query("SHOW COLUMNS FROM application LIKE 'cv_file_name'")->fetch();
$hasCvFileSizeColumn = (bool)$pdo->query("SHOW COLUMNS FROM application LIKE 'cv_file_size'")->fetch();
$hasCvFileTypeColumn = (bool)$pdo->query("SHOW COLUMNS FROM application LIKE 'cv_file_type'")->fetch();

$optionalSelects = [
    $hasParsedCvDataColumn ? 'a.parsed_cv_data' : "NULL AS parsed_cv_data",
    $hasCvParsingStatusColumn ? 'a.cv_parsing_status' : "'pending' AS cv_parsing_status",
    $hasCvFileNameColumn ? 'a.cv_file_name' : "NULL AS cv_file_name",
    $hasCvFileSizeColumn ? 'a.cv_file_size' : "NULL AS cv_file_size",
    $hasCvFileTypeColumn ? 'a.cv_file_type' : "NULL AS cv_file_type",
];

$sql = "SELECT a.id, a.status, a.date_creation, a.lettre_de_motivation, a.cv,
               " . implode(', ', $optionalSelects) . ",
               o.id_offer, o.titre AS offre_titre, o.budget, o.duree, o.location, o.employment_type, o.experience_level, o.status AS offer_status,
               p.titre AS projet_titre,
               u.nom, u.prenom, u.email AS recruiter_email
        FROM application a
        JOIN application_offre ao ON ao.id_application = a.id
        JOIN offre_emploi o ON o.id_offer = ao.id_offre
        LEFT JOIN projet p ON p.id = o.id_projet
        JOIN `user` u ON u.id_user = o.id_recruteur
        WHERE a.id_user = ?";
$params = [$userId];

if ($search !== '') {
    $sql .= " AND (o.titre LIKE ? OR p.titre LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ?)";
    $needle = "%{$search}%";
    array_push($params, $needle, $needle, $needle, $needle);
}

if ($statusFilter !== '') {
    $sql .= ' AND a.status = ?';
    $params[] = $statusFilter;
}

$sql .= ' ORDER BY a.date_creation DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();

$statsStmt = $pdo->prepare('SELECT status, COUNT(*) AS total FROM application WHERE id_user = ? GROUP BY status');
$statsStmt->execute([$userId]);
$statusCounts = array_fill_keys($allowedStatuses, 0);
foreach ($statsStmt->fetchAll() as $row) {
    $statusCounts[(string)$row['status']] = (int)$row['total'];
}
$totalApplications = array_sum($statusCounts);
$activeApplications = $statusCounts['pending'] + $statusCounts['reviewed'] + $statusCounts['shortlisted'];

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function application_status_label(string $status): string
{
    return match ($status) {
        'pending' => 'Envoyée',
        'reviewed' => 'Consultée',
        'shortlisted' => 'Présélectionnée',
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
        'failed' => 'Lecture CV échouée',
        'parsing' => 'Lecture en cours',
        default => 'En attente',
    };
}

function cv_file_label(array $app): string
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
    <title>Mes candidatures | حرفة Tunisie</title>
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
        .hero-banner { background: linear-gradient(135deg, rgba(59, 35, 20, 0.74), rgba(46, 107, 62, 0.74)), url('<?php echo h(job_asset('assets/img/item_pics/IMG_3043.JPG')); ?>'); background-size: cover; background-position: center; color: #F5ECD7; padding: 3.4rem 0 2.6rem; border-bottom: 5px solid #8B5A3A; margin-bottom: 2rem; }
        .summary-tile { background:#fff; border:1px solid rgba(139,90,58,.16); border-left:5px solid #2E6B3E; border-radius:8px; box-shadow:0 8px 22px rgba(0,0,0,.07); padding:1rem; height:100%; }
        .summary-tile .value { font-size:1.7rem; font-weight:800; color:#3B2314; line-height:1; }
        .summary-tile .label { color:#6c5f55; font-size:.88rem; margin-top:.35rem; }
        .filter-panel { background:#fff; border:1px solid rgba(139,90,58,.16); border-radius:8px; padding:1rem; box-shadow:0 8px 22px rgba(0,0,0,.06); }
        .application-card { background:#fff; border:1px solid rgba(139,90,58,.18); border-radius:8px; box-shadow:0 10px 24px rgba(0,0,0,.08); padding:1.2rem; }
        .application-meta { display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap:.75rem; }
        .meta-box { background:#fbfaf7; border:1px solid rgba(139,90,58,.12); border-radius:8px; padding:.75rem; min-height:72px; }
        .meta-box .label { color:#7a6d63; font-size:.78rem; text-transform:uppercase; font-weight:700; }
        .meta-box .value { color:#3B2314; font-weight:700; margin-top:.2rem; overflow-wrap:anywhere; }
        .profile-chip { display:inline-flex; align-items:center; gap:.35rem; background:#eef6ef; color:#244f2e; border:1px solid rgba(46,107,62,.16); border-radius:999px; padding:.25rem .55rem; font-size:.82rem; margin:.15rem .2rem .15rem 0; }
        .timeline { display:flex; flex-wrap:wrap; gap:.35rem; align-items:center; }
        .timeline-step { border:1px solid rgba(139,90,58,.18); border-radius:999px; padding:.25rem .55rem; font-size:.78rem; color:#7a6d63; background:#fff; }
        .timeline-step.active { background:#2E6B3E; border-color:#2E6B3E; color:#fff; }
        .message-preview { max-height:92px; overflow:auto; background:#fbfaf7; border:1px solid rgba(139,90,58,.12); border-radius:8px; padding:.75rem; }
        .footer-section { background:#3B2314; color:#F5ECD7; padding:3rem 0 2rem; margin-top:3rem; }
        .footer-section a { color:#F5ECD7; text-decoration:none; }
        .footer-section a:hover { color:#C49A6C; }
        @media (max-width: 992px) { .application-meta { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 576px) { .application-meta { grid-template-columns: 1fr; } .application-card { padding:1rem; } }
    </style>
</head>
<body>
<?php render_app_header('my_applications'); ?>
<nav class="navbar navbar-expand-lg dashboard-navbar sticky-top legacy-page-navbar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo h($baseUrl . 'controllers/home.php'); ?>"><img src="<?php echo h(job_asset('assets/img/logo_herfa.png')); ?>" alt="Logo" height="40" style="margin-right:0.8rem;"><span style="color:#F5ECD7;font-weight:700;font-size:1.3rem;">حرفة Tunisie</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item"><a class="nav-link" href="<?php echo h($baseUrl . 'controllers/home.php'); ?>"><i class="fas fa-home me-1"></i>Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo h($baseUrl . 'controllers/offer_emploi/offres.php'); ?>"><i class="fas fa-briefcase me-1"></i>Emplois</a></li>
                <li class="nav-item"><a class="nav-link active" href="<?php echo h($baseUrl . 'controllers/offer_emploi/my_applications.php'); ?>"><i class="fas fa-list-check me-1"></i>Mes candidatures</a></li>
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
                <h2 class="mb-1">Mes candidatures</h2>
                <p class="mb-0">Suivez vos envois, vos CV transmis et l'avancement de chaque offre.</p>
            </div>
            <a class="btn btn-light" href="<?php echo h($baseUrl . 'controllers/offer_emploi/offres.php'); ?>"><i class="fas fa-plus me-1"></i> Trouver une offre</a>
        </div>
    </div>
</section>

<div class="container">
    <?php if ($notice === 'applied'): ?>
        <div class="alert alert-success d-flex align-items-center gap-2">
            <i class="fas fa-check-circle"></i>
            <div>Votre candidature a été envoyée avec succès. Vous pouvez vérifier ci-dessous les informations transmises au recruteur.</div>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="summary-tile"><div class="value"><?php echo (int)$totalApplications; ?></div><div class="label">Candidatures envoyées</div></div></div>
        <div class="col-md-3"><div class="summary-tile"><div class="value"><?php echo (int)$activeApplications; ?></div><div class="label">En cours de traitement</div></div></div>
        <div class="col-md-3"><div class="summary-tile"><div class="value"><?php echo (int)$statusCounts['shortlisted']; ?></div><div class="label">Présélections</div></div></div>
        <div class="col-md-3"><div class="summary-tile"><div class="value"><?php echo (int)$statusCounts['accepted']; ?></div><div class="label">Acceptées</div></div></div>
    </div>

    <form method="get" class="filter-panel row g-3 align-items-end mb-4">
        <div class="col-lg-6">
            <label class="form-label">Recherche</label>
            <input class="form-control" type="text" name="q" value="<?php echo h($search); ?>" placeholder="Offre, projet ou recruteur...">
        </div>
        <div class="col-lg-3">
            <label class="form-label">Statut</label>
            <select class="form-select" name="status">
                <option value="">Tous les statuts</option>
                <?php foreach ($allowedStatuses as $status): ?>
                    <option value="<?php echo h($status); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>><?php echo h(application_status_label($status)); ?> (<?php echo (int)$statusCounts[$status]; ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-3 d-flex gap-2">
            <button class="btn btn-primary flex-fill" type="submit"><i class="fas fa-search me-1"></i>Filtrer</button>
            <a class="btn btn-outline-secondary" href="<?php echo h($baseUrl . 'controllers/offer_emploi/my_applications.php'); ?>">Réinitialiser</a>
        </div>
    </form>

    <?php if (count($applications) === 0): ?>
        <div class="alert alert-light border text-center py-5">
            <h5 class="mb-2">Aucune candidature trouvée</h5>
            <p class="text-muted mb-3">Essayez un autre filtre ou consultez les offres disponibles.</p>
            <a class="btn btn-primary" href="<?php echo h($baseUrl . 'controllers/offer_emploi/offres.php'); ?>"><i class="fas fa-briefcase me-1"></i>Voir les offres</a>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($applications as $app): ?>
                <?php
                    $cvData = !empty($app['parsed_cv_data']) ? (json_decode((string)$app['parsed_cv_data'], true) ?: []) : [];
                    $skills = $cvData['skills'] ?? [];
                    $skills = is_array($skills) ? $skills : array_filter(array_map('trim', explode(',', (string)$skills)));
                    $cvDownloadUrl = (!empty($app['cv']) && preg_match('#^(?:public/)?uploads/cv/#', (string)$app['cv']))
                        ? $baseUrl . ltrim((string)$app['cv'], '/')
                        : '';
                    $currentStatus = (string)$app['status'];
                ?>
                <div class="col-12">
                    <article class="application-card">
                        <div class="d-flex justify-content-between flex-wrap gap-3 mb-3">
                            <div>
                                <div class="text-muted small mb-1"><i class="fas fa-calendar me-1"></i><?php echo h((string)$app['date_creation']); ?></div>
                                <h5 class="mb-1"><?php echo h((string)$app['offre_titre']); ?></h5>
                                <div class="text-muted small"><i class="fas fa-user-tie me-1"></i><?php echo h(trim((string)$app['prenom'] . ' ' . (string)$app['nom'])); ?> · <?php echo h((string)($app['recruiter_email'] ?? '')); ?></div>
                            </div>
                            <span class="badge <?php echo job_application_status_badge_class($currentStatus); ?> px-3 py-2 align-self-start"><?php echo h(application_status_label($currentStatus)); ?></span>
                        </div>

                        <div class="application-meta mb-3">
                            <div class="meta-box"><div class="label">Projet</div><div class="value"><?php echo h((string)($app['projet_titre'] ?? 'N/A')); ?></div></div>
                            <div class="meta-box"><div class="label">Budget</div><div class="value"><?php echo h((string)$app['budget']); ?> TND</div></div>
                            <div class="meta-box"><div class="label">Durée</div><div class="value"><?php echo h((string)$app['duree']); ?></div></div>
                            <div class="meta-box"><div class="label">Lieu</div><div class="value"><?php echo h((string)($app['location'] ?? 'Non précisé')); ?></div></div>
                        </div>

                        <div class="mb-3">
                            <div class="small fw-bold mb-2">Progression</div>
                            <div class="timeline">
                                <?php foreach (['pending', 'reviewed', 'shortlisted', 'accepted'] as $step): ?>
                                    <span class="timeline-step <?php echo $currentStatus === $step ? 'active' : ''; ?>"><?php echo h(application_status_label($step)); ?></span>
                                <?php endforeach; ?>
                                <?php if ($currentStatus === 'rejected'): ?>
                                    <span class="timeline-step active">Refusée</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-lg-7">
                                <div class="small fw-bold mb-2">Message envoyé</div>
                                <div class="message-preview"><?php echo nl2br(h((string)$app['lettre_de_motivation'])); ?></div>
                            </div>
                            <div class="col-lg-5">
                                <div class="small fw-bold mb-2">Profil transmis</div>
                                <div class="border rounded p-3 h-100">
                                    <div class="mb-2"><strong><?php echo h((string)($cvData['full_name'] ?? trim($userPrenom . ' ' . $userNom))); ?></strong></div>
                                    <?php if (!empty($cvData['professional_title'])): ?><div class="text-muted small mb-2"><?php echo h((string)$cvData['professional_title']); ?></div><?php endif; ?>
                                    <?php foreach (array_slice($skills, 0, 8) as $skill): ?>
                                        <span class="profile-chip"><i class="fas fa-check"></i><?php echo h((string)$skill); ?></span>
                                    <?php endforeach; ?>
                                    <?php if (empty($skills)): ?><div class="text-muted small">Aucune compétence structurée détectée.</div><?php endif; ?>
                                    <hr>
                                    <div class="small"><strong>CV:</strong> <?php echo h(cv_file_label($app)); ?> <?php echo h(file_size_label($app['cv_file_size'] ?? 0)); ?></div>
                                    <div class="small"><strong>Lecture:</strong> <?php echo h(parsing_status_label((string)($app['cv_parsing_status'] ?? 'pending'))); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 d-flex gap-2 flex-wrap">
                            <a class="btn btn-outline-primary btn-sm" href="<?php echo h($baseUrl . 'controllers/offer_emploi/offer_details.php?id_offer=' . (int)$app['id_offer']); ?>"><i class="fas fa-eye me-1"></i>Voir l'offre</a>
                            <?php if ($cvDownloadUrl !== ''): ?>
                                <a class="btn btn-outline-success btn-sm" href="<?php echo h($cvDownloadUrl); ?>" target="_blank" rel="noopener"><i class="fas fa-file-download me-1"></i>Télécharger mon CV</a>
                            <?php endif; ?>
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
