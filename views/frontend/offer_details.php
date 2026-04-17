<?php
// offer_details.php

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/Config.php';
require_auth();

$userId = (int)$_SESSION['user_id'];
$userNom = (string)($_SESSION['nom'] ?? 'Utilisateur');
$userPrenom = (string)($_SESSION['prenom'] ?? '');
$role = (string)($_SESSION['role'] ?? 'artisan');
$canManageOffers = in_array($role, ['recruteur', 'entrepreneur', 'admin'], true);

$idOffer = (int)($_GET['id_offer'] ?? 0);
if ($idOffer <= 0) {
    header('Location: offres.php');
    exit();
}

$hasImageColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'image_path'")->fetch();
$hasSkillsColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'skills_needed'")->fetch();
$hasLocationColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'location'")->fetch();
$hasContactEmailColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'contact_email'")->fetch();
$hasVerificationColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'verification_status'")->fetch();

$selectImage = $hasImageColumn ? 'o.image_path' : "'' AS image_path";
$selectSkills = $hasSkillsColumn ? 'o.skills_needed' : "'' AS skills_needed";
$selectLocation = $hasLocationColumn ? 'o.location' : "'' AS location";
$selectContact = $hasContactEmailColumn ? 'o.contact_email' : "'' AS contact_email";
$selectVerification = $hasVerificationColumn ? 'o.verification_status' : "'not_verified' AS verification_status";

$stmt = $pdo->prepare(
    "SELECT o.id_offer, o.titre, o.description, o.budget, o.duree, o.id_recruteur,
            {$selectImage}, {$selectSkills}, {$selectLocation}, {$selectContact}, {$selectVerification},
            u.nom, u.prenom, p.titre AS projet_titre
     FROM offre_emploi o
     JOIN `user` u ON u.id_user = o.id_recruteur
     LEFT JOIN projet p ON p.id = o.id_projet
     WHERE o.id_offer = ?
     LIMIT 1"
);
$stmt->execute([$idOffer]);
$offer = $stmt->fetch();

if (!$offer) {
    header('Location: offres.php');
    exit();
}

$isOwner = $canManageOffers && ((int)$offer['id_recruteur'] === $userId);

$isApplied = false;
if ($role === 'artisan') {
    $appliedStmt = $pdo->prepare(
        'SELECT a.id
         FROM application a
         JOIN application_offre ao ON ao.id_application = a.id
         WHERE a.id_user = ? AND ao.id_offre = ?
         LIMIT 1'
    );
    $appliedStmt->execute([$userId, $idOffer]);
    $isApplied = (bool)$appliedStmt->fetch();
}

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
    <title>Détails offre | حرفة Tunisie</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="/assets/css/styles.css" rel="stylesheet" />
    <style>
        .dashboard-navbar { background-color: rgba(59, 35, 20, 0.96) !important; padding: 0.8rem 0; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .dashboard-navbar .nav-link { color: #F5ECD7 !important; font-weight: 600; margin: 0 0.35rem; }
        .dashboard-navbar .nav-link:hover { color: #C49A6C !important; }
        .user-greeting { color: #C49A6C; font-weight: bold; margin-right: 1rem; }
        .btn-logout { background: #2E6B3E !important; color: #fff !important; padding: .45rem .9rem; border-radius: 4px; text-decoration:none; font-weight:600; }
        .hero-banner { background: linear-gradient(135deg, rgba(139, 90, 58, 0.72), rgba(46, 107, 62, 0.72)), url('assets/img/item_pics/IMG_3043.JPG'); background-size: cover; background-position: center; color: #F5ECD7; padding: 3.2rem 0; text-align: center; border-bottom: 5px solid #8B5A3A; margin-bottom: 2rem; }
        .offer-shell { background:#fff; border:1px solid rgba(139,90,58,.18); border-radius: 14px; box-shadow: 0 8px 24px rgba(0,0,0,.08); overflow:hidden; }
        .offer-shell .body { padding:1.5rem; }
        .offer-image { width:100%; height:320px; object-fit:cover; background:linear-gradient(135deg,#8B5A3A,#C49A6C); }
        .footer-section { background:#3B2314; color:#F5ECD7; padding:3rem 0 2rem; margin-top:3rem; }
        .footer-section a { color:#F5ECD7; text-decoration:none; }
        .footer-section a:hover { color:#C49A6C; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg dashboard-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="home.php">
            <img src="assets/img/logo_herfa.png" alt="Logo" height="40" style="margin-right:0.8rem;">
            <span style="color:#F5ECD7; font-weight:700; font-size:1.3rem;">حرفة Tunisie</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item"><a class="nav-link" href="home.php"><i class="fas fa-home me-1"></i>Accueil</a></li>
                <li class="nav-item"><a class="nav-link active" href="offres.php"><i class="fas fa-briefcase me-1"></i>Emplois</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php"><i class="fas fa-user me-1"></i>Profil</a></li>
                <li class="nav-item"><span class="user-greeting"><i class="fas fa-user-circle me-1"></i><?php echo h(trim($userPrenom . ' ' . $userNom)); ?></span></li>
                <li class="nav-item"><a class="btn-logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<section class="hero-banner"><div class="container"><h2 class="mb-0">Détails de l'offre</h2></div></section>

<div class="container">
    <div class="offer-shell">
        <?php if (!empty($offer['image_path'])): ?>
            <img src="<?php echo h((string)$offer['image_path']); ?>" class="offer-image" alt="Image offre">
        <?php endif; ?>
        <div class="body">
            <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
                <h3 class="mb-0"><?php echo h((string)$offer['titre']); ?></h3>
                <span class="badge bg-success fs-6"><?php echo h((string)$offer['budget']); ?> TND</span>
            </div>
            <?php
                $verificationStatus = (string)($offer['verification_status'] ?? 'not_verified');
                $verificationLabel = $verificationStatus === 'verified' ? 'Vérifiée par admin' : 'En attente de vérification';
                $verificationClass = $verificationStatus === 'verified' ? 'bg-success text-white' : 'bg-warning text-dark';
            ?>
            <p class="mb-2"><span class="badge <?php echo $verificationClass; ?> px-3 py-2"><?php echo h($verificationLabel); ?></span></p>
            <p class="text-muted mb-2"><i class="fas fa-user me-1"></i><?php echo h((string)$offer['prenom'] . ' ' . (string)$offer['nom']); ?></p>
            <p class="text-muted mb-2"><i class="fas fa-clock me-1"></i><?php echo h((string)$offer['duree']); ?></p>
            <p class="text-muted mb-2"><i class="fas fa-project-diagram me-1"></i><?php echo h((string)($offer['projet_titre'] ?? 'N/A')); ?></p>
            <?php if (!empty($offer['location'])): ?><p class="text-muted mb-2"><i class="fas fa-map-marker-alt me-1"></i><?php echo h((string)$offer['location']); ?></p><?php endif; ?>
            <?php if (!empty($offer['contact_email'])): ?><p class="text-muted mb-2"><i class="fas fa-envelope me-1"></i><?php echo h((string)$offer['contact_email']); ?></p><?php endif; ?>
            <hr>
            <p><?php echo nl2br(h((string)$offer['description'])); ?></p>
            <?php if (!empty($offer['skills_needed'])): ?>
                <div class="mb-3">
                    <?php foreach (array_filter(array_map('trim', explode(',', (string)$offer['skills_needed']))) as $skill): ?>
                        <span class="badge bg-light text-dark border me-1 mb-1"><?php echo h($skill); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-secondary" href="offres.php"><i class="fas fa-arrow-left me-1"></i>Retour</a>
                <?php if ($role === 'artisan'): ?>
                    <?php if ($isApplied): ?>
                        <span class="btn btn-secondary disabled"><i class="fas fa-check me-1"></i>Déjà postulée</span>
                    <?php else: ?>
                        <a class="btn btn-primary" href="apply_offre.php?id_offer=<?php echo (int)$offer['id_offer']; ?>"><i class="fas fa-paper-plane me-1"></i>Postuler</a>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($isOwner || $role === 'admin'): ?>
                    <a class="btn btn-outline-success" href="applications.php?id_offer=<?php echo (int)$offer['id_offer']; ?>"><i class="fas fa-users me-1"></i>Voir candidatures</a>
                    <a class="btn btn-outline-primary" href="offres.php?edit=<?php echo (int)$offer['id_offer']; ?>#offerFormCollapse"><i class="fas fa-edit me-1"></i>Modifier</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<footer class="footer-section">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4 mb-md-0"><img src="assets/img/logo_herfa.png" alt="Logo" height="50" class="mb-3"><p class="small">حرفة Tunisie - La plateforme dédiée à l'artisanat tunisien et à l'entrepreneuriat responsable.</p></div>
            <div class="col-md-2 mb-4 mb-md-0"><h6 class="mb-3">Liens rapides</h6><ul class="list-unstyled small"><li class="mb-2"><a href="home.php">Accueil</a></li><li class="mb-2"><a href="projets.php">Projets</a></li><li class="mb-2"><a href="formations.php">Formations</a></li><li class="mb-2"><a href="offres.php">Emplois</a></li></ul></div>
            <div class="col-md-3 mb-4 mb-md-0"><h6 class="mb-3">Ressources</h6><ul class="list-unstyled small"><li class="mb-2"><a href="#">Blog</a></li><li class="mb-2"><a href="#">FAQ</a></li><li class="mb-2"><a href="#">Support</a></li><li class="mb-2"><a href="#">Mentions légales</a></li></ul></div>
            <div class="col-md-3"><h6 class="mb-3">Contact</h6><ul class="list-unstyled small"><li class="mb-2"><i class="fas fa-envelope me-2"></i> contact@herfa.tn</li><li class="mb-2"><i class="fas fa-phone me-2"></i> +216 70 000 000</li><li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> Tunis, Tunisie</li></ul></div>
        </div>
        <hr class="mt-4 mb-3" style="border-color: rgba(245,236,215,0.2);">
        <div class="text-center small"><p class="mb-0">© <?php echo date('Y'); ?> حرفة Tunisie - Tous droits réservés</p></div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/scripts.js"></script>
</body>
</html>
