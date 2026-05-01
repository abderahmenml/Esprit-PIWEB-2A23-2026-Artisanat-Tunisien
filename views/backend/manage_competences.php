<?php
// manage_competences.php
// Add/remove competences for current profile via checkbox list

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/Config.php';
require_auth();

$userId = (int)$_SESSION['user_id'];

$profileTable = null;
if ($pdo->query("SHOW TABLES LIKE 'profil_professionnel'")->fetchColumn() !== false) {
    $profileTable = 'profil_professionnel';
} elseif ($pdo->query("SHOW TABLES LIKE 'profil_profetionnel'")->fetchColumn() !== false) {
    $profileTable = 'profil_profetionnel';
}

if ($profileTable === null) {
    header('Location: edit_profile.php');
    exit();
}

$profileStmt = $pdo->prepare("SELECT id_profil FROM {$profileTable} WHERE id_user = ? LIMIT 1");
$profileStmt->execute([$userId]);
$profile = $profileStmt->fetch();

if (!$profile) {
    header('Location: edit_profile.php');
    exit();
}

$idProfil = (int)$profile['id_profil'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected = $_POST['competences'] ?? [];
    if (!is_array($selected)) {
        $selected = [];
    }

    $pdo->beginTransaction();
    try {
        $del = $pdo->prepare('DELETE FROM profil_competences WHERE id_profil = ?');
        $del->execute([$idProfil]);

        if (count($selected) > 0) {
            $ins = $pdo->prepare('INSERT INTO profil_competences (id_profil, id_competences) VALUES (?, ?)');
            foreach ($selected as $idComp) {
                $ins->execute([$idProfil, (int)$idComp]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        die('Erreur sauvegarde compétences: ' . htmlspecialchars($e->getMessage()));
    }

    header('Location: profile.php');
    exit();
}

$allComp = $pdo->query(
    'SELECT id_competence AS id_competences, nom_competence AS competence, description, 0 AS nombre_projets FROM competences ORDER BY nom_competence ASC'
)->fetchAll();
$selectedStmt = $pdo->prepare('SELECT id_competences FROM profil_competences WHERE id_profil = ?');
$selectedStmt->execute([$idProfil]);
$selectedIds = array_map('intval', array_column($selectedStmt->fetchAll(), 'id_competences'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Compétences | حرفة Tunisie</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
     <link href="/assets/css/styles.css" rel="stylesheet" />
</head>
<body>
<header>
    <h1 class="site-heading text-center text-faded d-none d-lg-block">
        <span class="site-heading-upper text-primary mb-3">Votre espace</span>
        <span class="site-heading-lower">Gérer Compétences</span>
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
            <form method="post" class="row g-2">
                <?php foreach ($allComp as $comp): ?>
                    <div class="col-12">
                        <label>
                            <input type="checkbox" name="competences[]" value="<?php echo (int)$comp['id_competences']; ?>"
                                <?php echo in_array((int)$comp['id_competences'], $selectedIds, true) ? 'checked' : ''; ?> />
                            <strong><?php echo htmlspecialchars($comp['competence']); ?></strong>
                            — <?php echo htmlspecialchars((string)$comp['description']); ?>
                            (<?php echo (int)$comp['nombre_projets']; ?> projets)
                        </label>
                    </div>
                <?php endforeach; ?>

                <div class="col-12 mt-3 text-center">
                    <button class="btn btn-primary btn-xl" type="submit">Sauvegarder</button>
                    <a class="btn btn-primary btn-xl" href="profile.php">Retour</a>
                </div>
            </form>
        </div>
    </div>
</section>

<footer class="footer text-faded text-center py-5">
    <div class="container"><p class="m-0 small">Copyright &copy; حرفة Tunisie 2026</p></div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/scripts.js"></script>
</body>
</html>
