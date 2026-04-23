<?php
// my_applications.php

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/Config.php';
require_once dirname(__DIR__) . '/partials/job_ui.php';
require_role('artisan');

$userId = (int)$_SESSION['user_id'];
$userNom = (string)($_SESSION['nom'] ?? 'Utilisateur');
$userPrenom = (string)($_SESSION['prenom'] ?? '');
$notice = trim((string)($_GET['notice'] ?? ''));
$search = trim((string)($_GET['q'] ?? ''));

$sql = "SELECT a.id, a.status, a.date_creation, a.lettre_de_motivation,
               o.id_offer, o.titre AS offre_titre, o.budget, o.duree,
               p.titre AS projet_titre,
               u.nom, u.prenom
        FROM application a
        JOIN application_offre ao ON ao.id_application = a.id
        JOIN offre_emploi o ON o.id_offer = ao.id_offre
        LEFT JOIN projet p ON p.id = o.id_projet
        JOIN `user` u ON u.id_user = o.id_recruteur
        WHERE a.id_user = ?";
$params = [$userId];

if ($search !== '') {
    $sql .= " AND (o.titre LIKE ? OR p.titre LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$sql .= ' ORDER BY a.date_creation DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
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
        .dashboard-navbar { background-color: rgba(59, 35, 20, 0.96) !important; padding: 0.8rem 0; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .dashboard-navbar .nav-link { color: #F5ECD7 !important; font-weight: 600; margin: 0 0.35rem; }
        .dashboard-navbar .nav-link:hover { color: #C49A6C !important; }
        .user-greeting { color: #C49A6C; font-weight: bold; margin-right: 1rem; }
        .btn-logout { background: #2E6B3E !important; color: #fff !important; padding: .45rem .9rem; border-radius: 4px; text-decoration:none; font-weight:600; }
        .hero-banner { background: linear-gradient(135deg, rgba(139, 90, 58, 0.72), rgba(46, 107, 62, 0.72)), url('<?php echo h(job_asset('assets/img/item_pics/IMG_3043.JPG')); ?>'); background-size: cover; background-position: center; color: #F5ECD7; padding: 3.2rem 0; text-align: center; border-bottom: 5px solid #8B5A3A; margin-bottom: 2rem; }
        .application-card { background:#fff; border:1px solid rgba(139,90,58,.18); border-radius:14px; box-shadow:0 8px 24px rgba(0,0,0,.08); padding:1.2rem; }
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
                <li class="nav-item"><a class="nav-link" href="<?php echo h($baseUrl . 'controllers/offer_emploi/offres.php'); ?>"><i class="fas fa-briefcase me-1"></i>Emplois</a></li>
                <li class="nav-item"><a class="nav-link active" href="<?php echo h($baseUrl . 'controllers/offer_emploi/my_applications.php'); ?>"><i class="fas fa-list-check me-1"></i>Mes candidatures</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo h($baseUrl . 'controllers/user/profile.php'); ?>"><i class="fas fa-user me-1"></i>Profil</a></li>
                <li class="nav-item"><span class="user-greeting"><i class="fas fa-user-circle me-1"></i><?php echo h(trim($userPrenom . ' ' . $userNom)); ?></span></li>
                <li class="nav-item"><a class="btn-logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<section class="hero-banner"><div class="container"><h2 class="mb-1">Mes candidatures</h2><p class="mb-0">Suivez tous vos envois et leur statut.</p></div></section>

<div class="container">
    <?php if ($notice === 'applied'): ?>
        <div class="alert alert-success">Votre candidature a été envoyée avec succès.</div>
    <?php endif; ?>

    <form method="get" class="row g-2 align-items-end mb-4">
        <div class="col-md-8">
            <label class="form-label">Recherche</label>
            <input class="form-control" type="text" name="q" value="<?php echo h($search); ?>" placeholder="Titre offre/projet...">
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="fas fa-search me-1"></i>Filtrer</button>
            <a class="btn btn-outline-secondary" href="my_applications.php">Réinitialiser</a>
        </div>
    </form>

    <?php if (count($applications) === 0): ?>
        <div class="alert alert-light border">Aucune candidature trouvée.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($applications as $app): ?>
                <div class="col-12">
                    <div class="application-card">
                        <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                            <div>
                                <h5 class="mb-1"><?php echo h((string)$app['offre_titre']); ?></h5>
                                <div class="text-muted small"><i class="fas fa-project-diagram me-1"></i><?php echo h((string)($app['projet_titre'] ?? 'N/A')); ?></div>
                                <div class="text-muted small"><i class="fas fa-building me-1"></i><?php echo h((string)$app['prenom'] . ' ' . (string)$app['nom']); ?></div>
                            </div>
                            <span class="badge <?php echo job_application_status_badge_class((string)$app['status']); ?> px-3 py-2"><?php echo h((string)$app['status']); ?></span>
                        </div>
                        <p class="mb-2"><strong>Budget:</strong> <?php echo h((string)$app['budget']); ?> TND — <strong>Durée:</strong> <?php echo h((string)$app['duree']); ?></p>
                        <p class="mb-2"><strong>Message envoyé:</strong><br><?php echo nl2br(h((string)$app['lettre_de_motivation'])); ?></p>
                        <div class="text-muted small"><i class="fas fa-calendar me-1"></i><?php echo h((string)$app['date_creation']); ?></div>
                        <div class="mt-3 d-flex gap-2">
                            <a class="btn btn-outline-primary btn-sm" href="offer_details.php?id_offer=<?php echo (int)$app['id_offer']; ?>"><i class="fas fa-eye me-1"></i>Voir l'offre</a>
                        </div>
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
