<?php
// recruiter_dashboard.php
// Recruiter dashboard: own offers + applications + status update + applicant profile link

declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_role('recruteur');

$userNom = (string)($_SESSION['nom'] ?? 'Utilisateur');
$userPrenom = (string)($_SESSION['prenom'] ?? '');

$recruiterId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id'], $_POST['status'])) {
    $applicationId = (int)$_POST['application_id'];
    $status = trim($_POST['status']);
    if (in_array($status, ['pending', 'accepted', 'rejected'], true)) {
        $update = $pdo->prepare('UPDATE application SET status = ? WHERE id = ?');
        $update->execute([$status, $applicationId]);
    }
}

$offersStmt = $pdo->prepare('SELECT id_offer, titre, budget, duree FROM offre_emploi WHERE id_recruteur = ? ORDER BY id_offer DESC');
$offersStmt->execute([$recruiterId]);
$offers = $offersStmt->fetchAll();

$appStmt = $pdo->prepare(
    "SELECT o.id_offer, o.titre AS offre_titre,
            a.id AS application_id, a.lettre_de_motivation, a.cv, a.status, a.date_creation,
            u.id_user AS artisan_id, u.nom, u.prenom
     FROM offre_emploi o
     LEFT JOIN application_offre ao ON ao.id_offre = o.id_offer
     LEFT JOIN application a ON a.id = ao.id_application
     LEFT JOIN `user` u ON u.id_user = a.id_user
     WHERE o.id_recruteur = ?
     ORDER BY o.id_offer DESC, a.id DESC"
);
$appStmt->execute([$recruiterId]);
$applications = $appStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Dashboard Recruteur | حرفة Tunisie</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <style>
        .dashboard-navbar { background-color: rgba(59, 35, 20, 0.95) !important; padding: 1rem 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .dashboard-navbar .nav-link { color: #F5ECD7 !important; font-weight: 600; margin: 0 0.35rem; }
        .dashboard-navbar .nav-link:hover { color: #C49A6C !important; }
        .user-greeting { color: #C49A6C; font-weight: bold; margin-right: 1rem; }
        .btn-logout { background: #2E6B3E !important; color: white !important; padding: 0.45rem 0.9rem; border-radius: 4px; text-decoration: none; font-weight: 600; }
        .hero-banner { background: linear-gradient(135deg, rgba(139, 90, 58, 0.75), rgba(46, 107, 62, 0.75)), url('assets/img/item_pics/IMG_3043.JPG'); background-size: cover; background-position: center; color: #F5ECD7; padding: 3.2rem 0; text-align: center; border-bottom: 5px solid #8B5A3A; margin-bottom: 2rem; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg dashboard-navbar sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="home.php">
            <img src="assets/img/logo_herfa.png" alt="Logo" height="40" style="margin-right: 0.5rem;">
            <span style="color: #F5ECD7; font-weight: bold;">حرفة Tunisie</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="home.php">Accueil</a></li>
                <li class="nav-item"><a class="nav-link active" href="offres.php">Emplois</a></li>
                <li class="nav-item"><a class="nav-link" href="create_offre.php">Créer</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php">Profil</a></li>
                <li class="nav-item"><span class="user-greeting">👋 <?php echo htmlspecialchars(trim($userPrenom . ' ' . $userNom)); ?></span></li>
                <li class="nav-item"><a class="btn-logout" href="logout.php">Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<section class="hero-banner"><div class="container"><h2 class="mb-0">Dashboard Recruteur</h2></div></section>

<section class="page-section cta">
    <div class="container">
        <div class="cta-inner bg-faded rounded p-5">
            <p>
                <a class="btn btn-primary btn-xl" href="create_offre.php">Créer nouvelle offre</a>
            </p>

            <h4>Mes offres</h4>
            <?php if (count($offers) === 0): ?>
                <p>Aucune offre publiée.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($offers as $o): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($o['titre']); ?></strong>
                            — Budget: <?php echo htmlspecialchars((string)$o['budget']); ?>
                            — Durée: <?php echo htmlspecialchars((string)$o['duree']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <hr />
            <h4>Candidatures reçues</h4>
            <?php if (count($applications) === 0): ?>
                <p>Aucune candidature.</p>
            <?php else: ?>
                <?php foreach ($applications as $app): ?>
                    <?php if (empty($app['application_id'])) { continue; } ?>
                    <div class="bg-white rounded p-4 mb-3">
                        <p><strong>Offre:</strong> <?php echo htmlspecialchars((string)$app['offre_titre']); ?></p>
                        <p>
                            <strong>Candidat:</strong>
                            <?php echo htmlspecialchars((string)$app['prenom'] . ' ' . (string)$app['nom']); ?>
                            <?php if (!empty($app['artisan_id'])): ?>
                                — <a href="profile.php?user_id=<?php echo (int)$app['artisan_id']; ?>">Voir profil</a>
                            <?php endif; ?>
                        </p>
                        <p><strong>Lettre:</strong><br /><?php echo nl2br(htmlspecialchars((string)$app['lettre_de_motivation'])); ?></p>
                        <p><strong>CV:</strong> <?php echo htmlspecialchars((string)$app['cv']); ?></p>

                        <form method="post" class="row g-2 align-items-center">
                            <input type="hidden" name="application_id" value="<?php echo (int)$app['application_id']; ?>" />
                            <div class="col-md-5">
                                <select class="form-control" name="status">
                                    <option value="pending" <?php echo $app['status'] === 'pending' ? 'selected' : ''; ?>>pending</option>
                                    <option value="accepted" <?php echo $app['status'] === 'accepted' ? 'selected' : ''; ?>>accepted</option>
                                    <option value="rejected" <?php echo $app['status'] === 'rejected' ? 'selected' : ''; ?>>rejected</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-primary" type="submit">Mettre à jour</button>
                            </div>
                            <div class="col-md-4">
                                <small>Statut actuel: <?php echo htmlspecialchars((string)$app['status']); ?></small>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
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
