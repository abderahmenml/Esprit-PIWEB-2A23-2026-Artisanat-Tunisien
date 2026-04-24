<?php
// offer_details.php

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

$idOffer = (int)($_GET['id_offer'] ?? 0);
if ($idOffer <= 0) {
    header('Location: ' . $baseUrl . 'controllers/offer_emploi/offres.php');
    exit();
}

$hasImageColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'image_path'")->fetch();
$hasSkillsColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'skills_needed'")->fetch();
$hasLocationColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'location'")->fetch();
$hasContactEmailColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'contact_email'")->fetch();
$hasVerificationColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'verification_status'")->fetch();
$hasStatusColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'status'")->fetch();
$hasViewsColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'views_count'")->fetch();
$hasApplicationsColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'applications_count'")->fetch();
$hasEmploymentTypeColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'employment_type'")->fetch();
$hasExperienceLevelColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'experience_level'")->fetch();
$hasExpiresAtColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'expires_at'")->fetch();
$hasCreatedAtColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'created_at'")->fetch();

if ($hasViewsColumn) {
    $pdo->prepare('UPDATE offre_emploi SET views_count = COALESCE(views_count, 0) + 1 WHERE id_offer = ?')->execute([$idOffer]);
}

$selectImage = $hasImageColumn ? 'o.image_path' : "'' AS image_path";
$selectSkills = $hasSkillsColumn ? 'o.skills_needed' : "'' AS skills_needed";
$selectLocation = $hasLocationColumn ? 'o.location' : "'' AS location";
$selectContact = $hasContactEmailColumn ? 'o.contact_email' : "'' AS contact_email";
$selectVerification = $hasVerificationColumn ? 'o.verification_status' : "'not_verified' AS verification_status";
$selectStatus = $hasStatusColumn ? 'o.status' : "'published' AS status";
$selectViews = $hasViewsColumn ? 'o.views_count' : '0 AS views_count';
$selectApplications = $hasApplicationsColumn ? 'o.applications_count' : '0 AS applications_count';
$selectEmploymentType = $hasEmploymentTypeColumn ? 'o.employment_type' : "'' AS employment_type";
$selectExperienceLevel = $hasExperienceLevelColumn ? 'o.experience_level' : "'' AS experience_level";
$selectExpiresAt = $hasExpiresAtColumn ? 'o.expires_at' : "NULL AS expires_at";
$selectCreatedAt = $hasCreatedAtColumn ? 'o.created_at' : "NULL AS created_at";

$stmt = $pdo->prepare(
    "SELECT o.id_offer, o.titre, o.description, o.budget, o.duree, o.id_recruteur,
            {$selectImage}, {$selectSkills}, {$selectLocation}, {$selectContact}, {$selectVerification},
            {$selectStatus}, {$selectViews}, {$selectApplications}, {$selectEmploymentType}, {$selectExperienceLevel},
            {$selectExpiresAt}, {$selectCreatedAt},
            u.nom, u.prenom, u.email AS recruiter_email, p.titre AS projet_titre
     FROM offre_emploi o
     JOIN `user` u ON u.id_user = o.id_recruteur
     LEFT JOIN projet p ON p.id = o.id_projet
     WHERE o.id_offer = ?
     LIMIT 1"
);
$stmt->execute([$idOffer]);
$offer = $stmt->fetch();

if (!$offer) {
    header('Location: ' . $baseUrl . 'controllers/offer_emploi/offres.php');
    exit();
}

$isOwner = $canManageOffers && ((int)$offer['id_recruteur'] === $userId);
$isApplied = false;
$applicationStatus = '';

if ($role === 'artisan') {
    $appliedStmt = $pdo->prepare(
        'SELECT a.status
         FROM application a
         JOIN application_offre ao ON ao.id_application = a.id
         WHERE a.id_user = ? AND ao.id_offre = ?
         LIMIT 1'
    );
    $appliedStmt->execute([$userId, $idOffer]);
    $existingApplication = $appliedStmt->fetch();
    $isApplied = (bool)$existingApplication;
    $applicationStatus = $existingApplication ? (string)$existingApplication['status'] : '';
}

$applicationCountStmt = $pdo->prepare('SELECT COUNT(*) FROM application_offre WHERE id_offre = ?');
$applicationCountStmt->execute([$idOffer]);
$actualApplicationsCount = (int)$applicationCountStmt->fetchColumn();

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function offer_image_url(array $offer, string $baseUrl): string
{
    $path = trim((string)($offer['image_path'] ?? ''));
    if ($path === '') {
        return '';
    }
    $path = str_replace('\\', '/', $path);
    if (preg_match('#^(https?:)?//#i', $path) || str_starts_with($path, 'data:')) {
        return $path;
    }
    if (str_starts_with($path, 'uploads/')) {
        return $baseUrl . 'controllers/offer_emploi/' . $path;
    }
    if (str_starts_with($path, '/')) {
        return $path;
    }
    return $baseUrl . ltrim($path, '/');
}

function application_status_label(string $status): string
{
    return match ($status) {
        'pending' => 'Envoyée',
        'reviewed' => 'Consultée',
        'shortlisted' => 'Présélectionnée',
        'interview' => 'Entretien',
        'accepted' => 'Acceptée',
        'rejected' => 'Refusée',
        default => ucfirst($status),
    };
}

function split_offer_skills(string $skills): array
{
    return array_values(array_filter(array_unique(array_map('trim', preg_split('/[,;|\n]+/', $skills) ?: []))));
}

$skills = split_offer_skills((string)($offer['skills_needed'] ?? ''));
$imageUrl = offer_image_url($offer, $baseUrl);
$verificationStatus = (string)($offer['verification_status'] ?? 'not_verified');
$isVerified = $verificationStatus === 'verified';
$offerStatus = (string)($offer['status'] ?? 'published');
$isClosed = in_array($offerStatus, ['closed', 'completed'], true);
$createdAt = !empty($offer['created_at']) ? date('d/m/Y', strtotime((string)$offer['created_at'])) : '';
$expiresAt = !empty($offer['expires_at']) ? date('d/m/Y', strtotime((string)$offer['expires_at'])) : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title><?php echo h((string)$offer['titre']); ?> | حرفة Tunisie</title>
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
        .detail-hero { background:#3B2314; color:#F5ECD7; position:relative; overflow:hidden; border-bottom:5px solid #8B5A3A; }
        .detail-hero-media { position:absolute; inset:0; background-size:cover; background-position:center; opacity:.28; }
        .detail-hero-media::after { content:''; position:absolute; inset:0; background:linear-gradient(90deg, rgba(59,35,20,.96), rgba(59,35,20,.70), rgba(46,107,62,.70)); }
        .detail-hero-content { position:relative; padding:3.4rem 0 2.7rem; }
        .hero-title { max-width:820px; font-size:clamp(2rem, 4vw, 3.4rem); line-height:1.05; font-weight:800; }
        .trust-badge { display:inline-flex; align-items:center; gap:.4rem; border-radius:999px; padding:.35rem .7rem; font-weight:700; font-size:.86rem; }
        .trust-badge.verified { background:#e9f6ec; color:#244f2e; }
        .trust-badge.pending { background:#fff5d8; color:#6c4d00; }
        .metric-row { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:.75rem; margin-top:1.5rem; }
        .metric { background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18); border-radius:8px; padding:.85rem; backdrop-filter: blur(2px); }
        .metric .label { opacity:.8; font-size:.78rem; text-transform:uppercase; font-weight:800; }
        .metric .value { font-size:1.05rem; font-weight:800; margin-top:.2rem; overflow-wrap:anywhere; }
        .content-card { background:#fff; border:1px solid rgba(139,90,58,.16); border-radius:8px; box-shadow:0 10px 24px rgba(0,0,0,.08); padding:1.25rem; }
        .sidebar-card { background:#fff; border:1px solid rgba(139,90,58,.16); border-radius:8px; box-shadow:0 10px 24px rgba(0,0,0,.08); padding:1.1rem; position:sticky; top:92px; }
        .skill-chip { display:inline-flex; align-items:center; gap:.35rem; background:#eef6ef; color:#244f2e; border:1px solid rgba(46,107,62,.16); border-radius:999px; padding:.3rem .6rem; font-size:.84rem; margin:.18rem .22rem .18rem 0; }
        .info-list { display:grid; gap:.65rem; }
        .info-item { display:flex; align-items:flex-start; gap:.65rem; color:#4e463f; }
        .info-item i { color:#2E6B3E; margin-top:.22rem; min-width:18px; }
        .section-label { color:#7a6d63; font-size:.78rem; text-transform:uppercase; font-weight:800; margin-bottom:.55rem; }
        .footer-section { background:#3B2314; color:#F5ECD7; padding:3rem 0 2rem; margin-top:3rem; }
        .footer-section a { color:#F5ECD7; text-decoration:none; }
        .footer-section a:hover { color:#C49A6C; }
        @media (max-width: 992px) { .metric-row { grid-template-columns:repeat(2, minmax(0, 1fr)); } .sidebar-card { position:static; } }
        @media (max-width: 576px) { .metric-row { grid-template-columns:1fr; } .detail-hero-content { padding:2.5rem 0 2rem; } }
    </style>
</head>
<body>
<?php render_app_header('jobs'); ?>
<nav class="navbar navbar-expand-lg dashboard-navbar sticky-top legacy-page-navbar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo h($baseUrl . 'controllers/home.php'); ?>">
            <img src="<?php echo h(job_asset('assets/img/logo_herfa.png')); ?>" alt="Logo" height="40" style="margin-right:0.8rem;">
            <span style="color:#F5ECD7; font-weight:700; font-size:1.3rem;">حرفة Tunisie</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item"><a class="nav-link" href="<?php echo h($baseUrl . 'controllers/home.php'); ?>"><i class="fas fa-home me-1"></i>Accueil</a></li>
                <li class="nav-item"><a class="nav-link active" href="<?php echo h($baseUrl . 'controllers/offer_emploi/offres.php'); ?>"><i class="fas fa-briefcase me-1"></i>Emplois</a></li>
                <?php if ($role === 'artisan'): ?><li class="nav-item"><a class="nav-link" href="<?php echo h($baseUrl . 'controllers/offer_emploi/my_applications.php'); ?>"><i class="fas fa-list-check me-1"></i>Mes candidatures</a></li><?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo h($baseUrl . 'controllers/user/profile.php'); ?>"><i class="fas fa-user me-1"></i>Profil</a></li>
                <li class="nav-item"><span class="user-greeting"><i class="fas fa-user-circle me-1"></i><?php echo h(trim($userPrenom . ' ' . $userNom)); ?></span></li>
                <li class="nav-item"><a class="btn-logout" href="<?php echo h($baseUrl . 'controllers/session_status.php?action=logout'); ?>"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<section class="detail-hero">
    <div class="detail-hero-media" style="background-image:url('<?php echo h($imageUrl !== '' ? $imageUrl : job_asset('assets/img/item_pics/IMG_3043.JPG')); ?>');"></div>
    <div class="container detail-hero-content">
        <div class="d-flex gap-2 flex-wrap mb-3">
            <span class="trust-badge <?php echo $isVerified ? 'verified' : 'pending'; ?>"><i class="fas <?php echo $isVerified ? 'fa-shield-alt' : 'fa-hourglass-half'; ?>"></i><?php echo $isVerified ? 'Offre vérifiée' : 'Vérification en attente'; ?></span>
            <span class="trust-badge pending"><i class="fas fa-circle"></i><?php echo h(job_offer_status_label($offerStatus)); ?></span>
        </div>
        <h1 class="hero-title mb-3"><?php echo h((string)$offer['titre']); ?></h1>
        <div class="d-flex flex-wrap gap-3 align-items-center">
            <span><i class="fas fa-user-tie me-1"></i><?php echo h(trim((string)$offer['prenom'] . ' ' . (string)$offer['nom'])); ?></span>
            <?php if (!empty($offer['projet_titre'])): ?><span><i class="fas fa-project-diagram me-1"></i><?php echo h((string)$offer['projet_titre']); ?></span><?php endif; ?>
            <?php if (!empty($offer['location'])): ?><span><i class="fas fa-map-marker-alt me-1"></i><?php echo h((string)$offer['location']); ?></span><?php endif; ?>
        </div>
        <div class="metric-row">
            <div class="metric"><div class="label">Budget</div><div class="value"><?php echo h((string)$offer['budget']); ?> TND</div></div>
            <div class="metric"><div class="label">Durée</div><div class="value"><?php echo h((string)$offer['duree']); ?></div></div>
            <div class="metric"><div class="label">Candidatures</div><div class="value"><?php echo (int)$actualApplicationsCount; ?></div></div>
            <div class="metric"><div class="label">Vues</div><div class="value"><?php echo (int)($offer['views_count'] ?? 0); ?></div></div>
        </div>
    </div>
</section>

<main class="container my-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <section class="content-card mb-4">
                <div class="section-label">Description</div>
                <div class="fs-6 lh-lg"><?php echo nl2br(h((string)$offer['description'])); ?></div>
            </section>

            <section class="content-card mb-4">
                <div class="section-label">Compétences recherchées</div>
                <?php if ($skills): ?>
                    <?php foreach ($skills as $skill): ?>
                        <span class="skill-chip"><i class="fas fa-check"></i><?php echo h($skill); ?></span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted mb-0">Aucune compétence spécifique n'a été précisée.</p>
                <?php endif; ?>
            </section>

            <section class="content-card">
                <div class="section-label">Informations recruteur</div>
                <div class="info-list">
                    <div class="info-item"><i class="fas fa-user-tie"></i><div><strong><?php echo h(trim((string)$offer['prenom'] . ' ' . (string)$offer['nom'])); ?></strong><br><span class="text-muted">Créateur de l'offre</span></div></div>
                    <?php if (!empty($offer['contact_email'])): ?>
                        <div class="info-item"><i class="fas fa-envelope"></i><div><?php echo h((string)$offer['contact_email']); ?></div></div>
                    <?php elseif ($isOwner || $role === 'admin'): ?>
                        <div class="info-item"><i class="fas fa-envelope"></i><div><?php echo h((string)$offer['recruiter_email']); ?></div></div>
                    <?php endif; ?>
                    <?php if ($createdAt !== ''): ?><div class="info-item"><i class="fas fa-calendar"></i><div>Publiée le <?php echo h($createdAt); ?></div></div><?php endif; ?>
                    <?php if ($expiresAt !== ''): ?><div class="info-item"><i class="fas fa-hourglass-end"></i><div>Expire le <?php echo h($expiresAt); ?></div></div><?php endif; ?>
                </div>
            </section>
        </div>

        <aside class="col-lg-4">
            <div class="sidebar-card">
                <div class="section-label">Résumé de l'offre</div>
                <div class="info-list mb-3">
                    <div class="info-item"><i class="fas fa-coins"></i><div><strong><?php echo h((string)$offer['budget']); ?> TND</strong><br><span class="text-muted">Budget annoncé</span></div></div>
                    <div class="info-item"><i class="fas fa-clock"></i><div><strong><?php echo h((string)$offer['duree']); ?></strong><br><span class="text-muted">Durée estimée</span></div></div>
                    <?php if (!empty($offer['employment_type'])): ?><div class="info-item"><i class="fas fa-briefcase"></i><div><?php echo h((string)$offer['employment_type']); ?></div></div><?php endif; ?>
                    <?php if (!empty($offer['experience_level'])): ?><div class="info-item"><i class="fas fa-layer-group"></i><div><?php echo h((string)$offer['experience_level']); ?></div></div><?php endif; ?>
                    <?php if (!empty($offer['location'])): ?><div class="info-item"><i class="fas fa-map-marker-alt"></i><div><?php echo h((string)$offer['location']); ?></div></div><?php endif; ?>
                </div>

                <div class="d-grid gap-2">
                    <?php if ($role === 'artisan'): ?>
                        <?php if ($isApplied): ?>
                            <a class="btn btn-success disabled" href="#"><i class="fas fa-check me-1"></i>Déjà postulé : <?php echo h(application_status_label($applicationStatus)); ?></a>
                            <a class="btn btn-outline-primary" href="<?php echo h($baseUrl . 'controllers/offer_emploi/my_applications.php'); ?>"><i class="fas fa-list-check me-1"></i>Suivre ma candidature</a>
                        <?php elseif ($isClosed): ?>
                            <span class="btn btn-secondary disabled"><i class="fas fa-lock me-1"></i>Offre clôturée</span>
                        <?php else: ?>
                            <a class="btn btn-primary btn-lg" href="<?php echo h($baseUrl . 'controllers/offer_emploi/apply_offre.php?id_offer=' . (int)$offer['id_offer']); ?>"><i class="fas fa-paper-plane me-1"></i>Postuler maintenant</a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($isOwner || $role === 'admin'): ?>
                        <a class="btn btn-outline-success" href="<?php echo h($baseUrl . 'controllers/offer_emploi/applications.php?id_offer=' . (int)$offer['id_offer']); ?>"><i class="fas fa-users me-1"></i>Voir candidatures</a>
                        <a class="btn btn-outline-primary" href="<?php echo h($baseUrl . 'controllers/offer_emploi/offres.php?edit=' . (int)$offer['id_offer'] . '#offerFormCollapse'); ?>"><i class="fas fa-edit me-1"></i>Modifier l'offre</a>
                    <?php endif; ?>
                    <a class="btn btn-outline-secondary" href="<?php echo h($baseUrl . 'controllers/offer_emploi/offres.php'); ?>"><i class="fas fa-arrow-left me-1"></i>Retour aux offres</a>
                </div>
            </div>
        </aside>
    </div>
</main>

<footer class="footer-section">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4 mb-md-0"><img src="<?php echo h(job_asset('assets/img/logo_herfa.png')); ?>" alt="Logo" height="50" class="mb-3"><p class="small">حرفة Tunisie - La plateforme dédiée à l'artisanat tunisien et à l'entrepreneuriat responsable.</p></div>
            <div class="col-md-2 mb-4 mb-md-0"><h6 class="mb-3">Liens rapides</h6><ul class="list-unstyled small"><li class="mb-2"><a href="<?php echo h($baseUrl . 'controllers/home.php'); ?>">Accueil</a></li><li class="mb-2"><a href="<?php echo h($baseUrl . 'controllers/projects/projets.php'); ?>">Projets</a></li><li class="mb-2"><a href="<?php echo h($baseUrl . 'controllers/formation/formations.php'); ?>">Formations</a></li><li class="mb-2"><a href="<?php echo h($baseUrl . 'controllers/offer_emploi/offres.php'); ?>">Emplois</a></li></ul></div>
            <div class="col-md-3 mb-4 mb-md-0"><h6 class="mb-3">Ressources</h6><ul class="list-unstyled small"><li class="mb-2"><a href="#">Blog</a></li><li class="mb-2"><a href="#">FAQ</a></li><li class="mb-2"><a href="#">Support</a></li><li class="mb-2"><a href="#">Mentions légales</a></li></ul></div>
            <div class="col-md-3"><h6 class="mb-3">Contact</h6><ul class="list-unstyled small"><li class="mb-2"><i class="fas fa-envelope me-2"></i> contact@herfa.tn</li><li class="mb-2"><i class="fas fa-phone me-2"></i> +216 70 000 000</li><li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> Tunis, Tunisie</li></ul></div>
        </div>
        <hr class="mt-4 mb-3" style="border-color: rgba(245,236,215,0.2);">
        <div class="text-center small"><p class="mb-0">© <?php echo date('Y'); ?> حرفة Tunisie - Tous droits réservés</p></div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo h(job_asset('assets/js/scripts.js')); ?>"></script>
</body>
</html>
