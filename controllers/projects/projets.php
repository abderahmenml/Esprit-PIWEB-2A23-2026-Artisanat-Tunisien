<?php
// projets.php
// List all projects with search/filter capabilities
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/Config.php';
require_auth();

$baseUrl = app_base_url();

$userId = (int)$_SESSION['user_id'];
$userName = $_SESSION['nom'] ?? 'Utilisateur';
$userPrenom = (string)($_SESSION['prenom'] ?? '');
$userRole = (string)($_SESSION['role'] ?? 'artisan');
$canManageProjects = in_array($userRole, ['entrepreneur', 'recruteur', 'admin'], true);

$hasImageColumn = (bool)$pdo->query("SHOW COLUMNS FROM projet LIKE 'image_path'")->fetch();
$hasSkillsColumn = (bool)$pdo->query("SHOW COLUMNS FROM projet LIKE 'skills_needed'")->fetch();
$hasCreatorColumn = (bool)$pdo->query("SHOW COLUMNS FROM projet LIKE 'id_createur'")->fetch();
$allSkills = $pdo->query('SELECT competence FROM competences ORDER BY competence ASC')->fetchAll();

$formOpen = in_array((string)($_GET['notice'] ?? ''), ['invalid', 'denied'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'create_project') {
    if (!$canManageProjects) {
        header('Location: projets.php?notice=denied');
        exit();
    }

    $titre = trim((string)($_POST['titre'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $budgetMin = (float)($_POST['budget_min'] ?? 0);
    $statusInput = trim((string)($_POST['status'] ?? 'active'));
    $status = in_array($statusInput, ['active', 'pending', 'completed'], true) ? $statusInput : 'active';
    $skillsNeeded = trim((string)($_POST['skills_needed'] ?? ''));
    $imagePath = null;

    if ($hasImageColumn && isset($_FILES['project_image']) && (int)($_FILES['project_image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $tmp = (string)($_FILES['project_image']['tmp_name'] ?? '');
        $name = basename((string)($_FILES['project_image']['name'] ?? ''));
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name) ?: ('project_' . time() . '.jpg');
        $ext = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $targetDir = __DIR__ . '/uploads/projects';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0775, true);
            }
            $fileName = uniqid('project_', true) . '.' . $ext;
            $targetFile = $targetDir . '/' . $fileName;
            if (@move_uploaded_file($tmp, $targetFile)) {
                $imagePath = 'uploads/projects/' . $fileName;
            }
        }
    }

    if ($titre !== '' && $description !== '') {
        $columns = ['titre', 'description', 'budget_min', 'status'];
        $values = [$titre, $description, $budgetMin, $status];
        $placeholders = ['?', '?', '?', '?'];

        if ($hasCreatorColumn) {
            $columns[] = 'id_createur';
            $values[] = $userId;
            $placeholders[] = '?';
        }

        if ($hasSkillsColumn) {
            $columns[] = 'skills_needed';
            $values[] = $skillsNeeded !== '' ? $skillsNeeded : null;
            $placeholders[] = '?';
        }

        if ($hasImageColumn) {
            $columns[] = 'image_path';
            $values[] = $imagePath;
            $placeholders[] = '?';
        }

        $columns[] = 'date_creation';
        $placeholders[] = 'NOW()';

        $insert = $pdo->prepare('INSERT INTO projet (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')');
        $insert->execute($values);
        header('Location: projets.php?notice=created');
        exit();
    }

    header('Location: projets.php?notice=invalid');
    exit();
}

// Get search/filter parameters
$search = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
$notice = isset($_GET['notice']) ? trim((string)$_GET['notice']) : '';

// Build query
$selectImageSql = $hasImageColumn ? 'image_path' : "'' AS image_path";
$query = "SELECT id, titre, description, budget_min, status, date_creation, $selectImageSql
          FROM projet
          WHERE 1=1";
$params = [];

if ($search !== '') {
    $query .= " AND (titre LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status !== '') {
    $query .= " AND status = ?";
    $params[] = $status;
}

$query .= " ORDER BY date_creation DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$projects = $stmt->fetchAll();

function app_url(string $path): string
{
    global $baseUrl;
    return $baseUrl . ltrim($path, '/');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="حرفة Tunisie - Projets" />
    <title>Projets | حرفة Tunisie</title>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars(app_url('public/assets/favicon.ico')); ?>" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="<?php echo htmlspecialchars(app_url('public/assets/css/styles.css')); ?>" rel="stylesheet" />
    <style>
        .dashboard-navbar { background-color: rgba(59, 35, 20, 0.96) !important; padding: 0.8rem 0; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .dashboard-navbar .nav-link { color: #F5ECD7 !important; font-weight: 600; margin: 0 0.35rem; transition: color .3s; }
        .dashboard-navbar .nav-link.active { color: #fff !important; }
        .dashboard-navbar .nav-link:hover { color: #C49A6C !important; }
        .user-greeting { color: #C49A6C; font-weight: bold; margin-right: 1rem; }
        .btn-logout { background: #2E6B3E !important; color: white !important; padding: 0.45rem 0.9rem; border-radius: 4px; text-decoration: none; font-weight: 600; }
        .btn-logout:hover { background: #1d4a2a !important; }
        .hero-banner { background: linear-gradient(135deg, rgba(139, 90, 58, 0.72), rgba(46, 107, 62, 0.72)), url('<?php echo htmlspecialchars(app_url('public/assets/img/item_pics/IMG_3043.JPG')); ?>'); background-size: cover; background-position: center; color: #F5ECD7; padding: 4rem 0; text-align: center; border-bottom: 5px solid #8B5A3A; margin-bottom: 2rem; }
        .hero-banner h1 { font-size: 2.4rem; font-weight: bold; margin-bottom: .6rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.25); }
        .hero-banner p { font-size: 1.05rem; margin: 0; }
        .section-shell { background: #F5ECD7; padding: 2rem; border-radius: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .section-title { font-size: 2rem; color: #2E6B3E; font-weight: bold; margin-bottom: 0.5rem; text-align: center; }
        .section-subtitle { font-size: 0.95rem; color: #8B5A3A; text-align: center; margin-bottom: 0; }
        .card-project { background: white; border-radius: 14px; padding: 1.5rem; box-shadow: 0 6px 18px rgba(0,0,0,0.08); transition: transform 0.3s, box-shadow 0.3s; border: 1px solid rgba(139,90,58,.12); height: 100%; }
        .card-project:hover { transform: translateY(-4px); box-shadow: 0 10px 24px rgba(0,0,0,0.12); }
        .project-create-shell { background: linear-gradient(135deg, #ffffff, #f8f3ea); border-radius: 16px; border: 1px solid rgba(139,90,58,.18); box-shadow: 0 14px 34px rgba(59,35,20,.10); overflow: hidden; }
        .project-create-shell .head { border-bottom: 1px solid rgba(139,90,58,.18); padding: 1rem 1.2rem; display:flex; justify-content:space-between; align-items:center; }
        .project-create-shell .body { padding: 1.2rem; }
        .skills-box { background: #fff; border: 1px solid rgba(139,90,58,.18); border-radius: 14px; padding: 1rem; }
        .skill-chip { display:inline-flex; align-items:center; gap:.45rem; padding:.45rem .75rem; border-radius:999px; border:1px solid rgba(139,90,58,.22); background:#fff; margin:.25rem .35rem .25rem 0; cursor:pointer; }
        .skill-chip input { margin:0; }
        .project-thumb { width:100%; height:180px; object-fit:cover; border-radius:10px; border:1px solid rgba(0,0,0,.08); margin-bottom:1rem; }
        .project-badge { display: inline-block; background: #2E6B3E; color: white; padding: 0.3rem 0.7rem; border-radius: 4px; font-size: 0.8rem; margin-bottom: 0.5rem; }
        .btn-explore { background: #2E6B3E; color: white; padding: 0.8rem 1.5rem; border-radius: 4px; text-decoration: none; display: inline-block; margin: 0.5rem 0; transition: background 0.3s; }
        .btn-explore:hover { background: #8B5A3A; }
        .search-section { background: #F5ECD7; padding: 2rem; margin-bottom: 2rem; border-radius: 8px; }
        .search-section input, .search-section select { padding: 0.7rem; margin-right: 0.5rem; border: 1px solid #999; border-radius: 4px; }
        .footer-section { background: #3B2314; color: #F5ECD7; padding: 3rem 0 2rem; margin-top: 3rem; }
        .footer-section a { color: #F5ECD7; text-decoration: none; transition: color 0.3s; }
        .footer-section a:hover { color: #C49A6C; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg dashboard-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo htmlspecialchars(app_url('controllers/home.php')); ?>">
            <img src="<?php echo htmlspecialchars(app_url('public/assets/img/logo_herfa.png')); ?>" alt="Logo" height="40" style="margin-right: 0.8rem;">
            <span style="color: #F5ECD7; font-weight: 700; font-size: 1.3rem;">حرفة Tunisie</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item"><a class="nav-link" href="<?php echo htmlspecialchars(app_url('controllers/home.php')); ?>"><i class="fas fa-home me-1"></i>Accueil</a></li>
                <li class="nav-item"><a class="nav-link active" href="<?php echo htmlspecialchars(app_url('controllers/projects/projets.php')); ?>"><i class="fas fa-project-diagram me-1"></i>Projets</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo htmlspecialchars(app_url('controllers/formation/formations.php')); ?>"><i class="fas fa-graduation-cap me-1"></i>Formations</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo htmlspecialchars(app_url('controllers/invester/invest.php')); ?>"><i class="fas fa-chart-line me-1"></i>Investir</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo htmlspecialchars(app_url('controllers/offer_emploi/offres.php')); ?>"><i class="fas fa-briefcase me-1"></i>Emplois</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo htmlspecialchars(app_url('controllers/user/profile.php')); ?>"><i class="fas fa-user me-1"></i>Profil</a></li>
                <li class="nav-item">
                    <span class="user-greeting">
                        <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars(trim($userPrenom . ' ' . $userName)); ?>
                    </span>
                </li>
                <li class="nav-item">
                    <a class="btn-logout" href="<?php echo htmlspecialchars(app_url('controllers/session_status.php?action=logout')); ?>">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<section class="hero-banner">
    <div class="container">
        <h1>Explorez les projets</h1>
        <p>Une interface moderne pour consulter, créer et suivre les projets artisanaux.</p>
    </div>
</section>

<!-- Page Header -->
<section class="page-section">
    <div class="container">
        <div class="section-shell mb-4">
            <h1 class="section-title">Tous les projets</h1>
            <p class="section-subtitle">Recherche, création et gestion des projets dans un style premium et cohérent avec la page d'accueil.</p>
        </div>

        <?php if ($notice === 'created'): ?>
            <div class="alert alert-success">Projet créé avec succès.</div>
        <?php elseif ($notice === 'invalid'): ?>
            <div class="alert alert-warning">Veuillez remplir correctement le formulaire du projet.</div>
        <?php elseif ($notice === 'denied'): ?>
            <div class="alert alert-danger">Vous n'avez pas les permissions pour créer un projet.</div>
        <?php endif; ?>

        <?php if ($canManageProjects): ?>
            <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-primary btn-xl" type="button" data-bs-toggle="collapse" data-bs-target="#projectCreateCollapse" aria-expanded="<?php echo $formOpen ? 'true' : 'false'; ?>" aria-controls="projectCreateCollapse">
                    + Ajouter un projet
                </button>
            </div>

            <div class="collapse <?php echo $formOpen ? 'show' : ''; ?> mb-4" id="projectCreateCollapse">
            <div class="project-create-shell">
                <div class="head">
                    <div>
                        <h4 class="m-0" style="color:#2E6B3E;">Créer un nouveau projet</h4>
                        <small class="text-muted">Décris clairement l'idée, les besoins et les compétences attendues.</small>
                    </div>
                    <span class="badge bg-success">Formulaire moderne</span>
                </div>
                <div class="body">
                    <form method="post" enctype="multipart/form-data" class="row g-3">
                        <input type="hidden" name="action" value="create_project" />
                        <div class="col-lg-8">
                            <label class="form-label">Titre du projet</label>
                            <input type="text" class="form-control form-control-lg" name="titre" placeholder="Ex: Atelier Céramique Premium" required>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">Budget minimum (TND)</label>
                            <input type="number" min="0" step="0.01" class="form-control form-control-lg" name="budget_min" placeholder="1500" required>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">Statut</label>
                            <select class="form-select form-select-lg" name="status">
                                <option value="active">Actif</option>
                                <option value="pending">En attente</option>
                                <option value="completed">Complété</option>
                            </select>
                        </div>
                        <div class="col-lg-8">
                            <label class="form-label">Compétences nécessaires</label>
                            <input type="hidden" name="skills_needed" id="skills_needed_input" />
                            <div class="skills-box">
                                <div class="small text-muted mb-2">Cliquez sur les compétences à rechercher pour ce projet.</div>
                                <div id="skills-chips">
                                    <?php foreach ($allSkills as $skill): ?>
                                        <label class="skill-chip">
                                            <input type="checkbox" class="skill-check" value="<?php echo htmlspecialchars((string)$skill['competence']); ?>">
                                            <span><?php echo htmlspecialchars((string)$skill['competence']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php if (!$hasSkillsColumn): ?>
                                <small class="text-muted d-block mt-2">Ajoutez la colonne `skills_needed` pour enregistrer cette sélection.</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="5" placeholder="Décrivez le projet, objectifs, besoins et impact..." required></textarea>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">Image du projet <?php echo $hasImageColumn ? '' : '(activez la colonne SQL)'; ?></label>
                            <input type="file" class="form-control" name="project_image" accept="image/*" <?php echo $hasImageColumn ? '' : 'disabled'; ?>>
                            <?php if (!$hasImageColumn): ?>
                                <small class="text-muted">Ajoutez d'abord la colonne `image_path` dans la table `projet`.</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-12 d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn-explore" style="margin:0;">Créer le projet</button>
                            <a href="projets.php" class="btn btn-outline-secondary">Réinitialiser</a>
                        </div>
                    </form>
                </div>
            </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Search/Filter Section -->
<section class="page-section">
    <div class="container">
        <div class="search-section">
            <form method="GET" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center;">
                <input type="text" name="q" placeholder="Rechercher un projet..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; min-width: 250px;" />
                <select name="status" style="min-width: 150px;">
                    <option value="">Tous les statuts</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Actif</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Complété</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>En attente</option>
                </select>
                <button type="submit" class="btn-explore">Rechercher</button>
                <?php if ($search !== '' || $status !== ''): ?>
                    <a href="projets.php" class="btn-explore" style="background: #8B5A3A;">Réinitialiser</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (count($projects) === 0): ?>
            <p class="text-center" style="font-size: 1.1rem; color: #666;">Aucun projet trouvé.</p>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($projects as $proj): ?>
                    <div class="col-12 col-lg-4">
                    <div class="card-project">
                        <?php if (!empty($proj['image_path'])): ?>
                            <img class="project-thumb" src="<?php echo htmlspecialchars((string)$proj['image_path']); ?>" alt="Image projet">
                        <?php endif; ?>
                        <div class="project-badge"><?php echo htmlspecialchars((string)$proj['status']); ?></div>
                        <h4 style="color: #2E6B3E; margin-top: 1rem;"><?php echo htmlspecialchars((string)$proj['titre']); ?></h4>
                        <p style="color: #666; min-height: 60px;">
                            <?php echo htmlspecialchars(substr((string)$proj['description'], 0, 150)); ?>...
                        </p>
                        <?php if (!empty($proj['skills_needed'])): ?>
                            <div class="mb-2">
                                <?php foreach (array_filter(array_map('trim', explode(',', (string)$proj['skills_needed']))) as $skill): ?>
                                    <span class="badge bg-light text-dark border me-1 mb-1"><?php echo htmlspecialchars($skill); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <hr />
                        <p><strong>Budget minimum:</strong> <span style="color: #2E6B3E; font-weight: bold;"><?php echo (int)$proj['budget_min']; ?> TND</span></p>
                        <p><small>📅 Créé le: <?php echo htmlspecialchars(substr((string)$proj['date_creation'], 0, 10)); ?></small></p>
                        <div style="margin-top: 1rem;">
                            <a class="btn-explore">Voir les détails</a>
                        </div>
                    </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <p style="text-align: center; color: #999; margin-top: 2rem;">Total: <?php echo count($projects); ?> projet(s) trouvé(s)</p>
        <?php endif; ?>
    </div>
</section>

<footer class="footer-section">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4 mb-md-0">
                <img src="<?php echo htmlspecialchars(app_url('public/assets/img/logo_herfa.png')); ?>" alt="Logo" height="50" class="mb-3">
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
                    <li class="mb-2"><a href="<?php echo htmlspecialchars(app_url('controllers/home.php')); ?>">Accueil</a></li>
                    <li class="mb-2"><a href="<?php echo htmlspecialchars(app_url('controllers/projects/projets.php')); ?>">Projets</a></li>
                    <li class="mb-2"><a href="<?php echo htmlspecialchars(app_url('controllers/formation/formations.php')); ?>">Formations</a></li>
                    <li class="mb-2"><a href="<?php echo htmlspecialchars(app_url('controllers/offer_emploi/offres.php')); ?>">Emplois</a></li>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo htmlspecialchars(app_url('public/assets/js/scripts.js')); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const sync = () => {
        const values = [...document.querySelectorAll('.skill-check:checked')].map(cb => cb.value);
        const input = document.getElementById('skills_needed_input');
        if (input) input.value = values.join(', ');
    };
    document.querySelectorAll('.skill-check').forEach(cb => cb.addEventListener('change', sync));
    sync();
});
</script>
</body>
</html>
