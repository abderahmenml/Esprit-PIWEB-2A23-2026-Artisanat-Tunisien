<?php
declare(strict_types=1);
require __DIR__ . '/user/profile.php';
exit();
__halt_compiler();
<?php
// profile.php
// Module 6: display professional profile + linked offers/applications

declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_auth();

$viewerId = (int)$_SESSION['user_id'];
$requestedUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $viewerId;

// MANDATORY SQL from request:
// SELECT u.nom, u.prenom, p.*, c.competence
// FROM user u
// JOIN profil_profetionnel p ON u.id_user = p.id_user
// LEFT JOIN profil_competences pc ON p.id_profil = pc.id_profil
// LEFT JOIN competences c ON pc.id_competences = c.id_competences
// WHERE u.id_user = ?

$profileSql = "
    SELECT u.id_user, u.nom, u.prenom, u.email, p.id_profil, p.`specialité`, p.bio, p.experience, p.portfolio, p.date_creation,
           c.id_competences, c.competence, c.nombre_projets
    FROM `user` u
    LEFT JOIN profil_profetionnel p ON u.id_user = p.id_user
    LEFT JOIN profil_competences pc ON p.id_profil = pc.id_profil
    LEFT JOIN competences c ON pc.id_competences = c.id_competences
    WHERE u.id_user = ?
";
$profileStmt = $pdo->prepare($profileSql);
$profileStmt->execute([$requestedUserId]);
$rows = $profileStmt->fetchAll();

$userRow = $rows[0] ?? null;
$competences = [];
foreach ($rows as $r) {
    if (!empty($r['id_competences'])) {
        $competences[] = [
            'competence' => $r['competence'],
            'nombre_projets' => $r['nombre_projets'],
        ];
    }
}

// Link with module 5: user offers
$offersStmt = $pdo->prepare(
    'SELECT o.id_offer, o.titre, o.budget, o.duree, o.description
     FROM offre_emploi o
     WHERE o.id_recruteur = ?
     ORDER BY o.id_offer DESC'
);
$offersStmt->execute([$requestedUserId]);
$userOffers = $offersStmt->fetchAll();

// Link with module 5: user applications (through profile owner)
$appStmt = $pdo->prepare(
    "SELECT ao.id_offre, o.titre, a.id, a.status, a.date_creation
     FROM application a
     JOIN application_offre ao ON ao.id_application = a.id
     JOIN offre_emploi o ON o.id_offer = ao.id_offre
     WHERE a.id_user = ?
     ORDER BY a.id DESC"
);
$appStmt->execute([$requestedUserId]);
$userApplications = $appStmt->fetchAll();

$isOwnProfile = $requestedUserId === $viewerId;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Profil | حرفة Tunisie</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="css/styles.css" rel="stylesheet" />
    <style>
        .dashboard-navbar { background-color: rgba(59, 35, 20, 0.95) !important; padding: 1rem 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .dashboard-navbar .nav-link { color: #F5ECD7 !important; font-weight: 600; margin: 0 0.35rem; transition: color .3s; }
        .dashboard-navbar .nav-link:hover { color: #C49A6C !important; }
        .dashboard-navbar .nav-link.active { color: #fff !important; }
        .user-greeting { color: #C49A6C; font-weight: bold; margin-right: 1rem; }
        .btn-logout { background: #2E6B3E !important; color: white !important; padding: 0.45rem 0.9rem; border-radius: 4px; text-decoration: none; font-weight: 600; }
        .btn-logout:hover { background: #1d4a2a !important; }
        .hero-banner { background: linear-gradient(135deg, rgba(139, 90, 58, 0.72), rgba(46, 107, 62, 0.72)), url('assets/img/item_pics/IMG_3043.JPG'); background-size: cover; background-position: center; color: #F5ECD7; padding: 4rem 0; text-align: center; border-bottom: 5px solid #8B5A3A; margin-bottom: 2rem; }
        .hero-banner h1 { font-size: 2.4rem; font-weight: bold; margin-bottom: .6rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.25); }
        .hero-banner p { font-size: 1.05rem; margin: 0; }
        .footer-section { background: #3B2314; color: #F5ECD7; padding: 3rem 0 2rem; margin-top: 3rem; }
        .footer-section a { color: #F5ECD7; text-decoration: none; transition: color 0.3s; }
        .footer-section a:hover { color: #C49A6C; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg dashboard-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="home.php">
            <img src="assets/img/logo_herfa.png" alt="Logo" height="40" style="margin-right: 0.8rem;">
            <span style="color: #F5ECD7; font-weight: 700; font-size: 1.3rem;">حرفة Tunisie</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item"><a class="nav-link" href="home.php"><i class="fas fa-home me-1"></i>Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="projets.php"><i class="fas fa-project-diagram me-1"></i>Projets</a></li>
                <li class="nav-item"><a class="nav-link" href="formations.php"><i class="fas fa-graduation-cap me-1"></i>Formations</a></li>
                <li class="nav-item"><a class="nav-link" href="invest.php"><i class="fas fa-chart-line me-1"></i>Investir</a></li>
                <li class="nav-item"><a class="nav-link" href="offres.php"><i class="fas fa-briefcase me-1"></i>Emplois</a></li>
                <li class="nav-item"><a class="nav-link active" href="profile.php"><i class="fas fa-user me-1"></i>Profil</a></li>
                <li class="nav-item">
                    <span class="user-greeting">
                        <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars(trim((string)$_SESSION['prenom'] . ' ' . (string)$_SESSION['nom'])); ?>
                    </span>
                </li>
                <li class="nav-item">
                    <a class="btn-logout" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<section class="hero-banner">
    <div class="container">
        <h1>Profil Professionnel</h1>
        <p>Votre espace personnel avec vos compétences, offres et candidatures.</p>
    </div>
</section>

<footer class="footer-section">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4 mb-md-0">
                <img src="assets/img/logo_herfa.png" alt="Logo" height="50" class="mb-3">
                <p class="small">حرفة Tunisie - La plateforme dédiée à l'artisanat tunisien et à l'entrepreneuriat responsable.</p>
                <div class="mt-3">
                    <a href="#" class="me-3"><i class="fab fa-facebook fa-lg"></i></a>
                    <a href="#" class="me-3"><i class="fab fa-instagram fa-lg"></i></a>
                    <a href="#" class="me-3"><i class="fab fa-linkedin fa-lg"></i></a>
                    <a href="#"><i class="fab fa-youtube fa-lg"></i></a>
                </div>
            </div>
            <div class="col-md-2 mb-4 mb-md-0">
                <h6 class="mb-3">Liens rapides</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="home.php">Accueil</a></li>
                    <li class="mb-2"><a href="projets.php">Projets</a></li>
                    <li class="mb-2"><a href="formations.php">Formations</a></li>
                    <li class="mb-2"><a href="offres.php">Emplois</a></li>
                </ul>
            </div>
            <div class="col-md-3 mb-4 mb-md-0">
                <h6 class="mb-3">Ressources</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="#">Blog</a></li>
                    <li class="mb-2"><a href="#">FAQ</a></li>
                    <li class="mb-2"><a href="#">Support</a></li>
                    <li class="mb-2"><a href="#">Mentions légales</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h6 class="mb-3">Contact</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><i class="fas fa-envelope me-2"></i> contact@herfa.tn</li>
                    <li class="mb-2"><i class="fas fa-phone me-2"></i> +216 70 000 000</li>
                    <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> Tunis, Tunisie</li>
                </ul>
            </div>
        </div>
        <hr class="mt-4 mb-3" style="border-color: rgba(245,236,215,0.2);">
        <div class="text-center small">
            <p class="mb-0">© <?php echo date('Y'); ?> حرفة Tunisie - Tous droits réservés</p>
        </div>
    </div>
</footer>

<section class="page-section cta">
    <div class="container">
        <div class="cta-inner bg-faded rounded p-5">
            <h2 class="section-heading mb-4">
                <span class="section-heading-upper">Profil de</span>
                <span class="section-heading-lower"><?php echo htmlspecialchars(($userRow['prenom'] ?? '') . ' ' . ($userRow['nom'] ?? '')); ?></span>
            </h2>

            <?php if (!$userRow): ?>
                <p>Utilisateur introuvable.</p>
            <?php else: ?>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($userRow['email']); ?></p>
                <p><strong>Spécialité:</strong> <?php echo htmlspecialchars((string)($userRow['specialité'] ?? '—')); ?></p>
                <p><strong>Bio:</strong> <?php echo nl2br(htmlspecialchars((string)($userRow['bio'] ?? '—'))); ?></p>
                <p><strong>Expérience:</strong> <?php echo nl2br(htmlspecialchars((string)($userRow['experience'] ?? '—'))); ?></p>
                <p><strong>Portfolio:</strong> <?php echo htmlspecialchars((string)($userRow['portfolio'] ?? '—')); ?></p>

                <?php if ($isOwnProfile): ?>
                    <div class="mb-3">
                        <a class="btn btn-primary btn-xl" href="edit_profile.php">Créer / Modifier le profil</a>
                        <a class="btn btn-primary btn-xl" href="manage_competences.php">Gérer les compétences</a>
                    </div>
                <?php endif; ?>

                <hr />
                <h4>Compétences</h4>
                <?php if (count($competences) === 0): ?>
                    <p>Aucune compétence liée.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($competences as $comp): ?>
                            <li>
                                <?php echo htmlspecialchars($comp['competence']); ?>
                                (<?php echo (int)$comp['nombre_projets']; ?> projets)
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <hr />
                <h4>Offres publiées par cet utilisateur</h4>
                <?php if (count($userOffers) === 0): ?>
                    <p>Aucune offre publiée.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($userOffers as $offer): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($offer['titre']); ?></strong>
                                — Budget: <?php echo htmlspecialchars((string)$offer['budget']); ?>
                                — Durée: <?php echo htmlspecialchars((string)$offer['duree']); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <hr />
                <h4>Candidatures de cet utilisateur</h4>
                <?php if (count($userApplications) === 0): ?>
                    <p>Aucune candidature.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($userApplications as $app): ?>
                            <li>
                                Offre: <?php echo htmlspecialchars($app['titre']); ?>
                                — Statut: <?php echo htmlspecialchars($app['status']); ?>
                                — Date: <?php echo htmlspecialchars($app['date_creation']); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<footer class="footer text-faded text-center py-5">
    <div class="container"><p class="m-0 small">Copyright &copy; حرفة Tunisie 2026</p></div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/scripts.js"></script>
</body>
</html>
