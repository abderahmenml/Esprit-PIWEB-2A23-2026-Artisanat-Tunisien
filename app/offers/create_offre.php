<?php
// create_offre.php
// Recruiter-only form + insert offer

declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_role('recruteur');

$userNom = (string)($_SESSION['nom'] ?? 'Utilisateur');
$userPrenom = (string)($_SESSION['prenom'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $budget = trim($_POST['budget'] ?? '0');
    $duree = trim($_POST['duree'] ?? '');
    $idProjet = (int)($_POST['id_projet'] ?? 0);

    if ($titre !== '' && $description !== '' && $idProjet > 0) {
        $sql = 'INSERT INTO offre_emploi (titre, description, budget, duree, id_projet, id_recruteur)
                VALUES (?, ?, ?, ?, ?, ?)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$titre, $description, $budget, $duree, $idProjet, (int)$_SESSION['user_id']]);

        header('Location: recruiter_dashboard.php');
        exit();
    }
}

// Load available projects (you can later restrict this by ownership if your schema tracks it)
$projects = $pdo->query('SELECT id, titre, budget_min, status FROM projet ORDER BY id DESC');
$projectRows = $projects->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Créer Offre | حرفة Tunisie</title>
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
                <li class="nav-item"><a class="nav-link" href="offres.php">Emplois</a></li>
                <li class="nav-item"><a class="nav-link active" href="create_offre.php">Créer</a></li>
                <li class="nav-item"><a class="nav-link" href="recruiter_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php">Profil</a></li>
                <li class="nav-item"><span class="user-greeting">👋 <?php echo htmlspecialchars(trim($userPrenom . ' ' . $userNom)); ?></span></li>
                <li class="nav-item"><a class="btn-logout" href="logout.php">Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<section class="hero-banner"><div class="container"><h2 class="mb-0">Créer une offre</h2></div></section>

<section class="page-section cta">
    <div class="container">
        <div class="cta-inner bg-faded rounded p-5">
            <form method="post" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Titre</label>
                    <input class="form-control" name="titre" required />
                </div>
                <div class="col-md-3">
                    <label class="form-label">Budget</label>
                    <input class="form-control" name="budget" type="number" step="0.01" required />
                </div>
                <div class="col-md-3">
                    <label class="form-label">Durée</label>
                    <input class="form-control" name="duree" placeholder="ex: 30 jours" required />
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="5" required></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Projet</label>
                    <select class="form-control" name="id_projet" required>
                        <option value="">-- Choisir un projet --</option>
                        <?php foreach ($projectRows as $p): ?>
                            <option value="<?php echo (int)$p['id']; ?>">
                                <?php echo htmlspecialchars($p['titre']); ?>
                                (Budget min: <?php echo htmlspecialchars((string)$p['budget_min']); ?>, status: <?php echo htmlspecialchars((string)$p['status']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 text-center">
                    <button class="btn btn-primary btn-xl" type="submit">Publier l'offre</button>
                </div>
            </form>
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
