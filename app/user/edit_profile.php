<?php
// edit_profile.php
// Form to create/update profil_profetionnel

declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_auth();

$userId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT * FROM profil_profetionnel WHERE id_user = ? LIMIT 1');
$stmt->execute([$userId]);
$profile = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Éditer Profil | حرفة Tunisie</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
    <link href="css/styles.css" rel="stylesheet" />
</head>
<body>
<header>
    <h1 class="site-heading text-center text-faded d-none d-lg-block">
        <span class="site-heading-upper text-primary mb-3">Votre espace</span>
        <span class="site-heading-lower">Créer / Éditer Profil</span>
    </h1>
</header>
<nav class="navbar navbar-expand-lg navbar-dark py-lg-4" id="mainNav">
    <div class="container">
        <a class="navbar-brand text-uppercase fw-bold d-lg-none" href="index.html">حرفة Tunisie</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item px-lg-4"><a class="nav-link text-uppercase active" href="profile.php">Profil</a></li>
                <li class="nav-item px-lg-4"><a class="nav-link text-uppercase" href="../offres.php">Offres</a></li>
                <li class="nav-item px-lg-4"><a class="nav-link text-uppercase" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<section class="page-section cta">
    <div class="container">
        <div class="cta-inner bg-faded rounded p-5">
            <form action="save_profile.php" method="post" enctype="multipart/form-data" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="specialite">Spécialité</label>
                    <input class="form-control" id="specialite" name="specialite" value="<?php echo htmlspecialchars((string)($profile['specialité'] ?? '')); ?>" required />
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="portfolio">Portfolio (URL ou texte)</label>
                    <input class="form-control" id="portfolio" name="portfolio" value="<?php echo htmlspecialchars((string)($profile['portfolio'] ?? '')); ?>" />
                </div>
                <div class="col-12">
                    <label class="form-label" for="bio">Bio</label>
                    <textarea class="form-control" id="bio" name="bio" rows="4" required><?php echo htmlspecialchars((string)($profile['bio'] ?? '')); ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="experience">Expérience</label>
                    <textarea class="form-control" id="experience" name="experience" rows="4" required><?php echo htmlspecialchars((string)($profile['experience'] ?? '')); ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="profile_image">Image profil (bonus)</label>
                    <input class="form-control" type="file" id="profile_image" name="profile_image" accept="image/*" />
                    <small>Si uploadée, le chemin est ajouté dans portfolio.</small>
                </div>
                <div class="col-12 text-center">
                    <button class="btn btn-primary btn-xl" type="submit">Enregistrer</button>
                    <a class="btn btn-primary btn-xl" href="profile.php">Retour Profil</a>
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
