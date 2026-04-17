<?php
// apply_offre.php
// Artisan-only apply form: insert into application + application_offre

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/Config.php';
require_role('artisan');

$userNom = (string)($_SESSION['nom'] ?? 'Utilisateur');
$userPrenom = (string)($_SESSION['prenom'] ?? '');

$idOffer = (int)($_GET['id_offer'] ?? $_POST['id_offer'] ?? 0);
if ($idOffer <= 0) {
    header('Location: offres.php');
    exit();
}

$notice = trim((string)($_GET['notice'] ?? ''));

$offerStmt = $pdo->prepare('SELECT id_offer, titre, description FROM offre_emploi WHERE id_offer = ? LIMIT 1');
$offerStmt->execute([$idOffer]);
$offer = $offerStmt->fetch();

if (!$offer) {
    die('Offre introuvable.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lettre = trim($_POST['lettre_de_motivation'] ?? '');
    $cvText = trim($_POST['cv_text'] ?? '');
    $cvPath = '';

    $alreadyStmt = $pdo->prepare(
        'SELECT a.id
         FROM application a
         JOIN application_offre ao ON ao.id_application = a.id
         WHERE a.id_user = ? AND ao.id_offre = ?
         LIMIT 1'
    );
    $alreadyStmt->execute([(int)$_SESSION['user_id'], $idOffer]);
    if ($alreadyStmt->fetch()) {
        header('Location: apply_offre.php?id_offer=' . $idOffer . '&notice=exists');
        exit();
    }

    // Bonus: CV upload as file
    if (isset($_FILES['cv_file']) && ($_FILES['cv_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $tmp = $_FILES['cv_file']['tmp_name'];
        $name = basename((string)$_FILES['cv_file']['name']);
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name) ?: ('cv_' . time() . '.pdf');
        $uploadDir = dirname(__DIR__, 2) . '/uploads/cv/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $target = $uploadDir . $safeName;
        if (move_uploaded_file($tmp, $target)) {
            $cvPath = 'uploads/cv/' . $safeName;
        }
    }

    $cvValue = $cvPath !== '' ? $cvPath : $cvText;

    if ($lettre !== '' && $cvValue !== '') {
        $pdo->beginTransaction();
        try {
            // SQL example requested by user:
            // INSERT INTO application (lettre_de_motivation, cv, status, date_creation)
            // VALUES (?, ?, 'pending', NOW());
            $insertApp = $pdo->prepare(
                 "INSERT INTO application (id_user, id_offer, lettre_de_motivation, cv, status, date_creation)
                  VALUES (?, ?, ?, ?, 'pending', NOW())"
            );
              $insertApp->execute([(int)$_SESSION['user_id'], $idOffer, $lettre, $cvValue]);
            $applicationId = (int)$pdo->lastInsertId();

            // INSERT INTO application_offre (id_offre, id_application)
            // VALUES (?, ?);
            $insertPivot = $pdo->prepare('INSERT INTO application_offre (id_offre, id_application) VALUES (?, ?)');
            $insertPivot->execute([$idOffer, $applicationId]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            die('Erreur candidature: ' . htmlspecialchars($e->getMessage()));
        }

        header('Location: my_applications.php?notice=applied');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Postuler | حرفة Tunisie</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="/assets/css/styles.css" rel="stylesheet" />
    <style>
        .dashboard-navbar { background-color: rgba(59, 35, 20, 0.96) !important; padding: 0.8rem 0; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .dashboard-navbar .nav-link { color: #F5ECD7 !important; font-weight: 600; margin: 0 0.35rem; }
        .dashboard-navbar .nav-link:hover { color: #C49A6C !important; }
        .user-greeting { color: #C49A6C; font-weight: bold; margin-right: 1rem; }
        .btn-logout { background: #2E6B3E !important; color: white !important; padding: 0.45rem 0.9rem; border-radius: 4px; text-decoration: none; font-weight: 600; }
        .hero-banner { background: linear-gradient(135deg, rgba(139, 90, 58, 0.75), rgba(46, 107, 62, 0.75)), url('assets/img/item_pics/IMG_3043.JPG'); background-size: cover; background-position: center; color: #F5ECD7; padding: 3.2rem 0; text-align: center; border-bottom: 5px solid #8B5A3A; margin-bottom: 2rem; }
        .footer-section { background: #3B2314; color: #F5ECD7; padding: 3rem 0 2rem; margin-top: 3rem; }
        .footer-section a { color: #F5ECD7; text-decoration: none; }
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
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item"><a class="nav-link" href="home.php"><i class="fas fa-home me-1"></i>Accueil</a></li>
                <li class="nav-item"><a class="nav-link active" href="offres.php"><i class="fas fa-briefcase me-1"></i>Emplois</a></li>
                <li class="nav-item"><a class="nav-link" href="my_applications.php"><i class="fas fa-list-check me-1"></i>Mes candidatures</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php"><i class="fas fa-user me-1"></i>Profil</a></li>
                <li class="nav-item"><span class="user-greeting"><i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars(trim($userPrenom . ' ' . $userNom)); ?></span></li>
                <li class="nav-item"><a class="btn-logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<section class="hero-banner"><div class="container"><h2 class="mb-0">Postuler à une offre</h2></div></section>

<section class="page-section cta">
    <div class="container">
        <div class="cta-inner bg-faded rounded p-5">
            <?php if ($notice === 'exists'): ?>
                <div class="alert alert-warning">Vous avez déjà postulé à cette offre.</div>
            <?php endif; ?>
            <h3><?php echo htmlspecialchars($offer['titre']); ?></h3>
            <p><?php echo nl2br(htmlspecialchars($offer['description'])); ?></p>
            <form method="post" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="id_offer" value="<?php echo (int)$idOffer; ?>" />
                <div class="col-12">
                    <label class="form-label">Lettre de motivation</label>
                    <textarea class="form-control" name="lettre_de_motivation" rows="6" required></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">CV (texte ou lien)</label>
                    <input class="form-control" name="cv_text" />
                </div>
                <div class="col-md-6">
                    <label class="form-label">CV (fichier)</label>
                    <input class="form-control" type="file" name="cv_file" accept=".pdf,.doc,.docx,.txt" />
                </div>
                <div class="col-12 text-center">
                    <button class="btn btn-primary btn-xl" type="submit">Envoyer candidature</button>
                </div>
            </form>
        </div>
    </div>
</section>

<footer class="footer-section">
    <div class="container"><div class="row"><div class="col-md-4 mb-4 mb-md-0"><img src="assets/img/logo_herfa.png" alt="Logo" height="50" class="mb-3"><p class="small">حرفة Tunisie - La plateforme dédiée à l'artisanat tunisien et à l'entrepreneuriat responsable.</p></div><div class="col-md-2 mb-4 mb-md-0"><h6 class="mb-3">Liens rapides</h6><ul class="list-unstyled small"><li class="mb-2"><a href="home.php">Accueil</a></li><li class="mb-2"><a href="projets.php">Projets</a></li><li class="mb-2"><a href="formations.php">Formations</a></li><li class="mb-2"><a href="offres.php">Emplois</a></li></ul></div><div class="col-md-3 mb-4 mb-md-0"><h6 class="mb-3">Ressources</h6><ul class="list-unstyled small"><li class="mb-2"><a href="#">Blog</a></li><li class="mb-2"><a href="#">FAQ</a></li><li class="mb-2"><a href="#">Support</a></li><li class="mb-2"><a href="#">Mentions légales</a></li></ul></div><div class="col-md-3"><h6 class="mb-3">Contact</h6><ul class="list-unstyled small"><li class="mb-2"><i class="fas fa-envelope me-2"></i> contact@herfa.tn</li><li class="mb-2"><i class="fas fa-phone me-2"></i> +216 70 000 000</li><li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> Tunis, Tunisie</li></ul></div></div><hr class="mt-4 mb-3" style="border-color: rgba(245,236,215,0.2);"><div class="text-center small"><p class="mb-0">© <?php echo date('Y'); ?> حرفة Tunisie - Tous droits réservés</p></div></div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/scripts.js"></script>
</body>
</html>
