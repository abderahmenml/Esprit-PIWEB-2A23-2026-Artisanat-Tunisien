<?php
// applications.php

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/Config.php';
require_once dirname(__DIR__) . '/partials/job_ui.php';
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
if ($idOffer <= 0) {
    header('Location: ' . $baseUrl . 'controllers/offer_emploi/offres.php');
    exit();
}

$offerStmt = $pdo->prepare('SELECT id_offer, titre, id_recruteur FROM offre_emploi WHERE id_offer = ? LIMIT 1');
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

$appStmt = $pdo->prepare(
    "SELECT a.id AS id_application, a.status, a.date_creation, a.lettre_de_motivation, a.cv,
            a.parsed_cv_data, a.cv_parsing_status, a.cv_file_name, a.cv_file_size, a.cv_file_type, a.cv_file_hash,
            u.id_user, u.nom, u.prenom, u.email
     FROM application_offre ao
     JOIN application a ON a.id = ao.id_application
     JOIN `user` u ON u.id_user = a.id_user
     WHERE ao.id_offre = ?
     ORDER BY a.date_creation DESC"
);
$appStmt->execute([$idOffer]);
$applications = $appStmt->fetchAll();

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function cv_download_label(array $app): string
{
    if (!empty($app['cv_file_name'])) {
        return (string)$app['cv_file_name'];
    }

    if (!empty($app['cv']) && preg_match('#^uploads/cv/#', (string)$app['cv'])) {
        return basename((string)$app['cv']);
    }

    return 'CV texte';
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
        .dashboard-navbar { background-color: rgba(59, 35, 20, 0.96) !important; padding: 0.8rem 0; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .dashboard-navbar .nav-link { color: #F5ECD7 !important; font-weight: 600; margin: 0 0.35rem; }
        .dashboard-navbar .nav-link:hover { color: #C49A6C !important; }
        .user-greeting { color: #C49A6C; font-weight: bold; margin-right: 1rem; }
        .btn-logout { background: #2E6B3E !important; color: #fff !important; padding: .45rem .9rem; border-radius: 4px; text-decoration:none; font-weight:600; }
        .hero-banner { background: linear-gradient(135deg, rgba(139, 90, 58, 0.72), rgba(46, 107, 62, 0.72)), url('<?php echo h(job_asset('assets/img/item_pics/IMG_3043.JPG')); ?>'); background-size: cover; background-position: center; color: #F5ECD7; padding: 3.2rem 0; text-align: center; border-bottom: 5px solid #8B5A3A; margin-bottom: 2rem; }
        .app-card { background:#fff; border:1px solid rgba(139,90,58,.18); border-radius:14px; box-shadow:0 8px 24px rgba(0,0,0,.08); padding:1.2rem; }
        .footer-section { background:#3B2314; color:#F5ECD7; padding:3rem 0 2rem; margin-top:3rem; }
        .footer-section a { color:#F5ECD7; text-decoration:none; }
        .footer-section a:hover { color:#C49A6C; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg dashboard-navbar sticky-top">
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

<section class="hero-banner"><div class="container"><h2 class="mb-1">Candidatures reçues</h2><p class="mb-0">Offre : <?php echo h((string)$offer['titre']); ?></p></div></section>

<div class="container">
    <?php if ($notice === 'updated'): ?>
        <div class="alert alert-success">Statut de candidature mis à jour.</div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="mb-0"><?php echo count($applications); ?> candidature(s)</h4>
        <a href="<?php echo h($baseUrl . 'controllers/offer_emploi/offres.php'); ?>" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Retour aux offres</a>
    </div>

    <?php if (count($applications) === 0): ?>
        <div class="alert alert-light border">Aucune candidature pour cette offre.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($applications as $app): ?>
                <div class="col-12">
                    <div class="app-card">
                        <div class="d-flex justify-content-between flex-wrap gap-2">
                            <div>
                                <h5 class="mb-1"><?php echo h((string)$app['prenom'] . ' ' . (string)$app['nom']); ?></h5>
                                <div class="text-muted small"><i class="fas fa-envelope me-1"></i><?php echo h((string)$app['email']); ?></div>
                                <div class="text-muted small"><i class="fas fa-calendar me-1"></i><?php echo h((string)$app['date_creation']); ?></div>
                            </div>
                            <span class="badge <?php echo job_application_status_badge_class((string)$app['status']); ?> px-3 py-2"><?php echo h((string)$app['status']); ?></span>
                        </div>
                        <hr>
                        <p><strong>Lettre de motivation</strong><br><?php echo nl2br(h((string)$app['lettre_de_motivation'])); ?></p>
                        <p><strong>CV</strong><br><?php echo h(cv_download_label($app)); ?></p>

                        <?php if (!empty($app['parsed_cv_data'])): ?>
                            <?php $cvData = json_decode((string)$app['parsed_cv_data'], true) ?: []; ?>
                            <div class="border rounded p-3 bg-light mb-3">
                                <h6 class="mb-2">Profil candidat structuré</h6>
                                <div class="small"><strong>Nom:</strong> <?php echo h((string)($cvData['full_name'] ?? '')); ?></div>
                                <div class="small"><strong>Email:</strong> <?php echo h((string)($cvData['email'] ?? '')); ?></div>
                                <div class="small"><strong>Compétences:</strong> <?php echo h(is_array($cvData['skills'] ?? null) ? implode(', ', $cvData['skills']) : (string)($cvData['skills'] ?? '')); ?></div>
                                <div class="small"><strong>Expérience:</strong> <?php echo h((string)($cvData['experience'] ?? '')); ?></div>
                                <div class="small"><strong>Formation:</strong> <?php echo h((string)($cvData['education'] ?? '')); ?></div>
                                <div class="small"><strong>Parsing:</strong> <?php echo h((string)($app['cv_parsing_status'] ?? 'pending')); ?></div>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3 d-flex flex-wrap gap-2">
                            <a class="btn btn-outline-primary btn-sm" href="<?php echo h($baseUrl . 'controllers/offer_emploi/apply_offre.php?id_offer=' . (int)$idOffer); ?>"><i class="fas fa-eye me-1"></i>Ouvrir le CV / candidature</a>
                            <?php if (!empty($app['cv']) && preg_match('#^uploads/cv/#', (string)$app['cv'])): ?>
                                <a class="btn btn-outline-success btn-sm" href="<?php echo h(job_asset(ltrim((string)$app['cv'], '/'))); ?>" target="_blank" rel="noopener"><i class="fas fa-download me-1"></i>Télécharger le CV</a>
                            <?php endif; ?>
                        </div>

                        <form method="post" action="<?php echo h($baseUrl . 'controllers/offer_emploi/update_application.php'); ?>" class="d-flex flex-wrap gap-2 align-items-center">
                            <input type="hidden" name="id_offer" value="<?php echo (int)$idOffer; ?>">
                            <input type="hidden" name="id_application" value="<?php echo (int)$app['id_application']; ?>">
                            <input type="hidden" name="csrf" value="<?php echo h($offerCsrf); ?>">
                            <select class="form-select" name="status" style="max-width:220px;">
                                <option value="pending" <?php echo $app['status'] === 'pending' ? 'selected' : ''; ?>>pending</option>
                                <option value="accepted" <?php echo $app['status'] === 'accepted' ? 'selected' : ''; ?>>accepted</option>
                                <option value="rejected" <?php echo $app['status'] === 'rejected' ? 'selected' : ''; ?>>rejected</option>
                            </select>
                            <button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i>Mettre à jour</button>
                            <a class="btn btn-outline-primary" href="<?php echo h($baseUrl . 'controllers/user/profile.php?user_id=' . (int)$app['id_user']); ?>"><i class="fas fa-id-card me-1"></i>Profil candidat</a>
                        </form>
                    </div>
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
