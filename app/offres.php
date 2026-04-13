<?php
declare(strict_types=1);
require __DIR__ . '/offers/offres.php';
exit();
__halt_compiler();
<?php
// offres.php
// Enhanced offer CRUD + filters + applications with modern UI
// Version: 2.0 - Enhanced with better UX, security, and features

declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_auth();

$userId = (int)$_SESSION['user_id'];
$userNom = (string)($_SESSION['nom'] ?? 'Utilisateur');
$userPrenom = (string)($_SESSION['prenom'] ?? '');
$role = (string)($_SESSION['role'] ?? 'artisan');
$canManageOffers = in_array($role, ['recruteur', 'entrepreneur', 'admin'], true);
$search = trim($_GET['q'] ?? '');
$competenceFilter = trim($_GET['competence'] ?? '');
$editOfferId = (int)($_GET['edit'] ?? 0);
$notice = trim($_GET['notice'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

// Fetch data for dropdowns
$projects = $pdo->query('SELECT id, titre, budget_min, status FROM projet ORDER BY titre ASC')->fetchAll();
$competences = $pdo->query('SELECT competence FROM competences ORDER BY competence ASC')->fetchAll();
$hasImageColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'image_path'")->fetch();
$hasSkillsColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'skills_needed'")->fetch();
$hasLocationColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'location'")->fetch();
$hasContactEmailColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'contact_email'")->fetch();
$hasStatusColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'status'")->fetch();
$hasCreatedAtColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'created_at'")->fetch();
$formOpen = in_array($notice, ['invalid', 'denied', 'created', 'updated'], true) || $editOfferId > 0;

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if (!$canManageOffers && in_array($action, ['create_offer', 'update_offer', 'delete_offer'], true)) {
        header('Location: offres.php?notice=denied');
        exit();
    }

    if ($action === 'create_offer') {
        $titre = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $budget = trim($_POST['budget'] ?? '0');
        $duree = trim($_POST['duree'] ?? '');
        $idProjet = (int)($_POST['id_projet'] ?? 0);
        $location = trim((string)($_POST['location'] ?? ''));
        $contactEmail = trim((string)($_POST['contact_email'] ?? ''));

        if ($titre !== '' && $description !== '' && $duree !== '' && $idProjet > 0) {
            // Handle image upload if column exists
            $imagePath = null;
            if ($hasImageColumn && isset($_FILES['offer_image']) && $_FILES['offer_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/uploads/offers/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext = pathinfo($_FILES['offer_image']['name'], PATHINFO_EXTENSION);
                $filename = 'offer_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['offer_image']['tmp_name'], $uploadDir . $filename)) {
                    $imagePath = 'uploads/offers/' . $filename;
                }
            }

            $skillsNeeded = $hasSkillsColumn ? trim((string)($_POST['skills_needed'] ?? '')) : '';

            $columns = ['titre', 'description', 'budget', 'duree', 'id_projet', 'id_recruteur'];
            $values = [$titre, $description, $budget, $duree, $idProjet, $userId];
            $placeholders = ['?', '?', '?', '?', '?', '?'];

            if ($hasLocationColumn) {
                $columns[] = 'location';
                $values[] = ($location !== '' ? $location : null);
                $placeholders[] = '?';
            }
            if ($hasContactEmailColumn) {
                $columns[] = 'contact_email';
                $values[] = ($contactEmail !== '' ? $contactEmail : null);
                $placeholders[] = '?';
            }
            if ($hasImageColumn) {
                $columns[] = 'image_path';
                $values[] = $imagePath;
                $placeholders[] = '?';
            }
            if ($hasSkillsColumn) {
                $columns[] = 'skills_needed';
                $values[] = ($skillsNeeded !== '' ? $skillsNeeded : null);
                $placeholders[] = '?';
            }

            $stmt = $pdo->prepare('INSERT INTO offre_emploi (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')');
            $stmt->execute($values);
            header('Location: offres.php?notice=created');
            exit();
        }

        header('Location: offres.php?notice=invalid');
        exit();
    }

    if ($action === 'update_offer') {
        $idOffer = (int)($_POST['id_offer'] ?? 0);
        $titre = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $budget = trim($_POST['budget'] ?? '0');
        $duree = trim($_POST['duree'] ?? '');
        $idProjet = (int)($_POST['id_projet'] ?? 0);
        $location = trim($_POST['location'] ?? '');
        $contactEmail = trim($_POST['contact_email'] ?? '');

        $selectOwnOfferCols = 'id_offer' . ($hasImageColumn ? ', image_path' : '');
        $ownOffer = $pdo->prepare('SELECT ' . $selectOwnOfferCols . ' FROM offre_emploi WHERE id_offer = ? AND id_recruteur = ? LIMIT 1');
        $ownOffer->execute([$idOffer, $userId]);
        $existing = $ownOffer->fetch();

        if ($existing && $titre !== '' && $description !== '' && $duree !== '' && $idProjet > 0) {
            $imagePath = $hasImageColumn ? (string)($existing['image_path'] ?? '') : null;
            if ($hasImageColumn && isset($_FILES['offer_image']) && $_FILES['offer_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/uploads/offers/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext = pathinfo($_FILES['offer_image']['name'], PATHINFO_EXTENSION);
                $filename = 'offer_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['offer_image']['tmp_name'], $uploadDir . $filename)) {
                    // Delete old image if exists
                    if ($imagePath && file_exists(__DIR__ . '/' . $imagePath)) {
                        unlink(__DIR__ . '/' . $imagePath);
                    }
                    $imagePath = 'uploads/offers/' . $filename;
                }
            }

            $skillsNeeded = $hasSkillsColumn ? trim((string)($_POST['skills_needed'] ?? '')) : '';

            $setParts = ['titre = ?', 'description = ?', 'budget = ?', 'duree = ?', 'id_projet = ?'];
            $values = [$titre, $description, $budget, $duree, $idProjet];

            if ($hasLocationColumn) {
                $setParts[] = 'location = ?';
                $values[] = ($location !== '' ? $location : null);
            }
            if ($hasContactEmailColumn) {
                $setParts[] = 'contact_email = ?';
                $values[] = ($contactEmail !== '' ? $contactEmail : null);
            }
            if ($hasImageColumn) {
                $setParts[] = 'image_path = ?';
                $values[] = $imagePath;
            }
            if ($hasSkillsColumn) {
                $setParts[] = 'skills_needed = ?';
                $values[] = ($skillsNeeded !== '' ? $skillsNeeded : null);
            }

            $values[] = $idOffer;
            $values[] = $userId;

            $stmt = $pdo->prepare('UPDATE offre_emploi SET ' . implode(', ', $setParts) . ' WHERE id_offer = ? AND id_recruteur = ?');
            $stmt->execute($values);
            header('Location: offres.php?notice=updated');
            exit();
        }

        header('Location: offres.php?notice=invalid');
        exit();
    }

    if ($action === 'delete_offer') {
        $idOffer = (int)($_POST['id_offer'] ?? 0);

        $selectOwnOfferCols = 'id_offer' . ($hasImageColumn ? ', image_path' : '');
        $ownOffer = $pdo->prepare('SELECT ' . $selectOwnOfferCols . ' FROM offre_emploi WHERE id_offer = ? AND id_recruteur = ? LIMIT 1');
        $ownOffer->execute([$idOffer, $userId]);
        $existing = $ownOffer->fetch();

        if ($existing) {
            // Delete associated image
            if ($hasImageColumn && !empty($existing['image_path']) && file_exists(__DIR__ . '/' . $existing['image_path'])) {
                unlink(__DIR__ . '/' . $existing['image_path']);
            }
            $stmt = $pdo->prepare('DELETE FROM offre_emploi WHERE id_offer = ? AND id_recruteur = ?');
            $stmt->execute([$idOffer, $userId]);
            header('Location: offres.php?notice=deleted');
            exit();
        }
    }
}

// Fetch edit offer data
$editOffer = null;
if ($editOfferId > 0 && $canManageOffers) {
    $editStmt = $pdo->prepare('SELECT * FROM offre_emploi WHERE id_offer = ? AND id_recruteur = ? LIMIT 1');
    $editStmt->execute([$editOfferId, $userId]);
    $editOffer = $editStmt->fetch() ?: null;
}

// Fetch user's own offers
$myOffers = [];
if ($canManageOffers) {
    $myOffersStmt = $pdo->prepare(
        'SELECT o.id_offer, o.titre, o.budget, o.duree, ' . ($hasStatusColumn ? 'o.status' : '"" AS status') . ', p.titre AS projet_titre
         FROM offre_emploi o
         LEFT JOIN projet p ON p.id = o.id_projet
         WHERE o.id_recruteur = ?
         ORDER BY o.id_offer DESC
         LIMIT 10'
    );
    $myOffersStmt->execute([$userId]);
    $myOffers = $myOffersStmt->fetchAll();
}

// Build main offers query with pagination
$sql = "SELECT o.*, u.nom, u.prenom, p.titre AS projet_titre
        FROM offre_emploi o
        JOIN `user` u ON o.id_recruteur = u.id_user
        LEFT JOIN projet p ON p.id = o.id_projet
    WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= ' AND (o.titre LIKE ? OR o.description LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if ($competenceFilter !== '') {
    if ($hasSkillsColumn) {
        $sql .= ' AND o.skills_needed LIKE ?';
        $params[] = '%' . $competenceFilter . '%';
    } else {
        $sql .= ' AND (o.titre LIKE ? OR o.description LIKE ?)';
        $params[] = '%' . $competenceFilter . '%';
        $params[] = '%' . $competenceFilter . '%';
    }
}

// Count total for pagination
$countSql = str_replace('SELECT o.*, u.nom, u.prenom, p.titre AS projet_titre', 'SELECT COUNT(DISTINCT o.id_offer) as total', $sql);
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalOffers = (int)$countStmt->fetch()['total'];
$totalPages = ceil($totalOffers / $perPage);
$offset = ($page - 1) * $perPage;

$sortColumn = $hasCreatedAtColumn ? 'o.created_at' : 'o.id_offer';
$sql .= ' ORDER BY ' . $sortColumn . ' DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$offers = $stmt->fetchAll();

// Get applied offers for artisans
$appliedOfferIds = [];
if ($role === 'artisan') {
    $appliedStmt = $pdo->prepare(
        'SELECT ao.id_offre
         FROM application a
         JOIN application_offre ao ON ao.id_application = a.id
         WHERE a.id_user = ?'
    );
    $appliedStmt->execute([$userId]);
    $appliedOfferIds = array_map('intval', array_column($appliedStmt->fetchAll(), 'id_offre'));
}

// Helper function
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
    <title>Offres d'emploi | حرفة Tunisie</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="css/styles.css" rel="stylesheet" />
    <style>
        :root {
            --primary-brown: #8B5A3A;
            --primary-green: #2E6B3E;
            --cream: #F5ECD7;
            --dark-brown: #3B2314;
        }
        body { background: #faf7f0; }
        .dashboard-navbar { background-color: rgba(59, 35, 20, 0.96) !important; padding: 0.8rem 0; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .dashboard-navbar .nav-link { color: #F5ECD7 !important; font-weight: 600; margin: 0 0.35rem; transition: color 0.3s; }
        .dashboard-navbar .nav-link.active { color: #fff !important; }
        .dashboard-navbar .nav-link:hover { color: #C49A6C !important; }
        .user-greeting { color: #C49A6C; font-weight: bold; margin-right: 1rem; }
        .btn-logout { background: #2E6B3E !important; color: white !important; padding: 0.45rem 0.9rem; border-radius: 4px; text-decoration: none; font-weight: 600; }
        .btn-logout:hover { background: #1d4a2a !important; }
        .hero-banner { background: linear-gradient(135deg, rgba(139, 90, 58, 0.72), rgba(46, 107, 62, 0.72)), url('assets/img/item_pics/IMG_3043.JPG'); background-size: cover; background-position: center; color: #F5ECD7; padding: 4rem 0; text-align: center; border-bottom: 5px solid #8B5A3A; margin-bottom: 2rem; }
        .hero-banner h1 { font-size: 2.4rem; font-weight: bold; margin-bottom: .6rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.25); }
        .hero-banner p { font-size: 1.05rem; margin: 0; }
        .section-shell { background: #F5ECD7; padding: 2rem; border-radius: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .section-title { font-size: 2rem; color: #2E6B3E; font-weight: bold; margin-bottom: 0.5rem; text-align: center; }
        .section-subtitle { font-size: 0.95rem; color: #8B5A3A; text-align: center; margin-bottom: 0; }
        .search-section { background: white; padding: 1.5rem; border-radius: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 2rem; border: 1px solid rgba(139,90,58,.15); }
        .offer-create-shell { background: white; border-radius: 20px; border: 1px solid rgba(139,90,58,.2); box-shadow: 0 20px 40px rgba(59,35,20,.12); overflow: hidden; }
        .offer-create-shell .head { background: linear-gradient(135deg, #f8f3ea, #fff); border-bottom: 2px solid rgba(139,90,58,.2); padding: 1.2rem 1.5rem; }
        .offer-create-shell .body { padding: 1.8rem; }
        .offer-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 1.8rem; }
        .skills-box { background: #faf7f0; border: 1px solid rgba(139,90,58,.2); border-radius: 14px; padding: 1.2rem; }
        .skill-chip { display: inline-flex; align-items: center; gap: .5rem; padding: .5rem 1rem; border-radius: 40px; border: 1px solid rgba(139,90,58,.25); background: white; margin: .3rem .4rem .3rem 0; cursor: pointer; transition: all 0.2s; }
        .skill-chip:hover { background: #f0e8db; transform: scale(1.02); }
        .skill-chip input { margin: 0; width: 18px; height: 18px; cursor: pointer; }
        .skill-chip span { font-size: 0.85rem; font-weight: 500; }
        .card-offer { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 8px 24px rgba(0,0,0,0.08); border: 1px solid rgba(139,90,58,.12); height: 100%; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .card-offer:hover { transform: translateY(-6px); box-shadow: 0 16px 32px rgba(0,0,0,0.12); }
        .offer-thumb { width: 100%; height: 200px; object-fit: cover; background: linear-gradient(135deg, #8B5A3A, #C49A6C); border-radius: 16px 16px 0 0; }
        .offer-card-body { padding: 1.5rem; display: flex; flex-direction: column; height: calc(100% - 200px); }
        .offer-title { color: #2E6B3E; font-size: 1.25rem; font-weight: 700; margin-bottom: 0.25rem; }
        .offer-meta { font-size: 0.85rem; color: #8B5A3A; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
        .offer-chip { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.35rem 0.85rem; border-radius: 40px; background: #f8f3ea; border: 1px solid rgba(139,90,58,.2); font-size: 0.8rem; font-weight: 500; }
        .offer-actions { display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: space-between; align-items: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(139,90,58,.1); }
        .status-badge { position: absolute; top: 1rem; right: 1rem; padding: 0.3rem 0.8rem; border-radius: 40px; font-size: 0.7rem; font-weight: bold; }
        .pagination-custom .page-link { color: #2E6B3E; border-radius: 8px; margin: 0 4px; }
        .pagination-custom .page-item.active .page-link { background-color: #2E6B3E; border-color: #2E6B3E; color: white; }
        .fade-in { animation: fadeIn 0.5s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .btn-primary { background: #2E6B3E; border-color: #2E6B3E; }
        .btn-primary:hover { background: #1e4a2c; border-color: #1e4a2c; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(46,107,62,0.3); }
        .btn-outline-primary { color: #2E6B3E; border-color: #2E6B3E; }
        .btn-outline-primary:hover { background: #2E6B3E; border-color: #2E6B3E; transform: translateY(-2px); }
        .alert { border-radius: 12px; border: none; }
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
                <li class="nav-item"><a class="nav-link active" href="offres.php"><i class="fas fa-briefcase me-1"></i>Emplois</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php"><i class="fas fa-user me-1"></i>Profil</a></li>
                <li class="nav-item">
                    <span class="user-greeting">
                        <i class="fas fa-user-circle me-1"></i><?php echo h(trim($userPrenom . ' ' . $userNom)); ?>
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
        <h1><i class="fas fa-briefcase me-3"></i>Offres d'emploi</h1>
        <p>Découvrez les meilleures opportunités professionnelles en Tunisie</p>
    </div>
</section>

<div class="container mb-5 fade-in">
    <!-- Notifications -->
    <?php if ($notice === 'created'): ?>
        <div class="alert alert-success shadow-sm"><i class="fas fa-check-circle me-2"></i>Offre créée avec succès !</div>
    <?php elseif ($notice === 'updated'): ?>
        <div class="alert alert-info shadow-sm"><i class="fas fa-edit me-2"></i>Offre mise à jour avec succès.</div>
    <?php elseif ($notice === 'deleted'): ?>
        <div class="alert alert-warning shadow-sm"><i class="fas fa-trash-alt me-2"></i>Offre supprimée avec succès.</div>
    <?php elseif ($notice === 'denied'): ?>
        <div class="alert alert-danger shadow-sm"><i class="fas fa-ban me-2"></i>Action non autorisée.</div>
    <?php endif; ?>

    <!-- User's Offers Section (for recruiters) -->
    <?php if ($canManageOffers && count($myOffers) > 0): ?>
    <div class="section-shell mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h3 class="mb-1"><i class="fas fa-user-tie me-2 text-success"></i>Mes offres publiées</h3>
                <p class="text-muted mb-0">Gérez vos annonces facilement</p>
            </div>
            <button class="btn btn-success" type="button" data-bs-toggle="collapse" data-bs-target="#offerFormCollapse">
                <i class="fas fa-plus me-1"></i>Nouvelle offre
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Titre</th><th>Projet</th><th>Budget</th><th>Durée</th><?php if ($hasStatusColumn): ?><th>Statut</th><?php endif; ?><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($myOffers as $mine): ?>
                    <tr>
                        <td><strong><?php echo h($mine['titre']); ?></strong></td>
                        <td><?php echo h((string)($mine['projet_titre'] ?? 'N/A')); ?></td>
                        <td><span class="badge bg-success"><?php echo h((string)$mine['budget']); ?> TND</span></td>
                        <td><i class="far fa-clock me-1"></i><?php echo h($mine['duree']); ?></td>
                        <?php if ($hasStatusColumn): ?>
                            <td><span class="badge bg-<?php echo ($mine['status'] === 'active' ? 'success' : 'secondary'); ?>"><?php echo h((string)$mine['status']); ?></span></td>
                        <?php endif; ?>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="offres.php?edit=<?php echo (int)$mine['id_offer']; ?>#offerFormCollapse"><i class="fas fa-edit"></i></a>
                            <form method="post" class="d-inline" onsubmit="return confirm('Supprimer cette offre ?');">
                                <input type="hidden" name="action" value="delete_offer" />
                                <input type="hidden" name="id_offer" value="<?php echo (int)$mine['id_offer']; ?>" />
                                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php elseif ($canManageOffers): ?>
    <div class="text-center mb-4">
        <button class="btn btn-success btn-lg" type="button" data-bs-toggle="collapse" data-bs-target="#offerFormCollapse">
            <i class="fas fa-plus-circle me-2"></i>Publier ma première offre
        </button>
    </div>
    <?php endif; ?>

    <!-- Offer Form Collapse -->
    <?php if ($canManageOffers): ?>
    <div class="collapse <?php echo $formOpen ? 'show' : ''; ?> mb-4" id="offerFormCollapse">
        <div class="offer-create-shell">
            <div class="head">
                <div>
                    <h3 class="mb-1"><i class="fas fa-<?php echo $editOffer ? 'edit' : 'plus-circle'; ?> me-2 text-success"></i><?php echo $editOffer ? 'Modifier l\'offre' : 'Créer une nouvelle offre'; ?></h3>
                    <p class="text-muted mb-0">Remplissez tous les champs pour publier une offre attractive</p>
                </div>
                <?php if ($editOffer): ?>
                    <a class="btn btn-outline-secondary" href="offres.php"><i class="fas fa-times me-1"></i>Annuler</a>
                <?php endif; ?>
            </div>
            <div class="body">
                <form method="post" enctype="multipart/form-data" class="row g-4">
                    <input type="hidden" name="action" value="<?php echo $editOffer ? 'update_offer' : 'create_offer'; ?>" />
                    <?php if ($editOffer): ?>
                        <input type="hidden" name="id_offer" value="<?php echo (int)$editOffer['id_offer']; ?>" />
                    <?php endif; ?>

                    <div class="col-md-6">
                        <label class="form-label fw-bold"><i class="fas fa-heading me-1 text-success"></i>Titre de l'offre *</label>
                        <input class="form-control form-control-lg" name="titre" value="<?php echo h($editOffer['titre'] ?? ''); ?>" placeholder="Ex: Développeur Web Senior" required />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold"><i class="fas fa-tags me-1 text-success"></i>Budget (TND) *</label>
                        <input class="form-control form-control-lg" name="budget" type="number" step="0.01" value="<?php echo h((string)($editOffer['budget'] ?? '0')); ?>" placeholder="0.00" required />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold"><i class="far fa-calendar-alt me-1 text-success"></i>Durée *</label>
                        <input class="form-control form-control-lg" name="duree" value="<?php echo h($editOffer['duree'] ?? ''); ?>" placeholder="ex: 3 mois" required />
                    </div>
                    <?php if ($hasLocationColumn): ?>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><i class="fas fa-map-marker-alt me-1 text-success"></i>Localisation</label>
                        <input class="form-control" name="location" value="<?php echo h($editOffer['location'] ?? ''); ?>" placeholder="Tunis, Sfax, Sousse, Télétravail..." />
                    </div>
                    <?php endif; ?>
                    <?php if ($hasContactEmailColumn): ?>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><i class="fas fa-envelope me-1 text-success"></i>Email de contact</label>
                        <input class="form-control" name="contact_email" type="email" value="<?php echo h($editOffer['contact_email'] ?? ''); ?>" placeholder="contact@entreprise.com" />
                    </div>
                    <?php endif; ?>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><i class="fas fa-project-diagram me-1 text-success"></i>Projet associé *</label>
                        <select class="form-select form-select-lg" name="id_projet" required>
                            <option value="">-- Sélectionner un projet --</option>
                            <?php foreach ($projects as $p): ?>
                                <option value="<?php echo (int)$p['id']; ?>" <?php echo isset($editOffer['id_projet']) && (int)$editOffer['id_projet'] === (int)$p['id'] ? 'selected' : ''; ?>>
                                    <?php echo h($p['titre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><i class="fas fa-image me-1 text-success"></i>Image de l'offre</label>
                        <input type="file" class="form-control" name="offer_image" accept="image/*">
                        <small class="text-muted">Format JPG, PNG, GIF. Taille max: 5MB</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold"><i class="fas fa-code me-1 text-success"></i>Compétences requises</label>
                        <input type="hidden" name="skills_needed" id="skills_needed_input" value="<?php echo h($editOffer['skills_needed'] ?? ''); ?>" />
                        <div class="skills-box">
                            <div class="small text-muted mb-3">Sélectionnez les compétences recherchées (plusieurs choix possibles)</div>
                            <div id="skills-chips">
                                <?php foreach ($competences as $skill): ?>
                                    <?php $skillValue = (string)$skill['competence']; $checked = !empty($editOffer['skills_needed']) && str_contains((string)$editOffer['skills_needed'], $skillValue); ?>
                                    <label class="skill-chip">
                                        <input type="checkbox" class="skill-check" value="<?php echo h($skillValue); ?>" <?php echo $checked ? 'checked' : ''; ?>>
                                        <span><?php echo h($skillValue); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold"><i class="fas fa-align-left me-1 text-success"></i>Description détaillée *</label>
                        <textarea class="form-control" name="description" rows="6" placeholder="Décrivez les missions, profil recherché, avantages..." required><?php echo h($editOffer['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary btn-lg px-5" type="submit"><i class="fas fa-<?php echo $editOffer ? 'save' : 'paper-plane'; ?> me-2"></i><?php echo $editOffer ? 'Mettre à jour' : 'Publier l\'offre'; ?></button>
                        <?php if ($editOffer): ?>
                            <a class="btn btn-outline-secondary btn-lg ms-2" href="offres.php">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Search & Filter Section -->
    <div class="search-section">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label fw-bold"><i class="fas fa-search me-1"></i>Rechercher</label>
                <input type="text" name="q" class="form-control" placeholder="Titre, description..." value="<?php echo h($search); ?>" />
            </div>
            <?php if ($hasSkillsColumn): ?>
            <div class="col-md-5">
                <label class="form-label fw-bold"><i class="fas fa-filter me-1"></i>Filtrer par compétence</label>
                <select name="competence" class="form-select">
                    <option value="">Toutes les compétences</option>
                    <?php foreach ($competences as $c): ?>
                        <option value="<?php echo h($c['competence']); ?>" <?php echo $competenceFilter === $c['competence'] ? 'selected' : ''; ?>><?php echo h($c['competence']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit"><i class="fas fa-search me-1"></i>Filtrer</button>
            </div>
        </form>
    </div>

    <!-- Results Count -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div>
            <h4 class="mb-0"><i class="fas fa-briefcase me-2 text-success"></i><?php echo $totalOffers; ?> offre(s) disponible(s)</h4>
        </div>
        <?php if ($search || $competenceFilter): ?>
            <a href="offres.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times me-1"></i>Effacer les filtres</a>
        <?php endif; ?>
    </div>

    <?php if ($role === 'artisan'): ?>
        <div class="mb-4">
            <a class="btn btn-outline-primary" href="my_applications.php"><i class="fas fa-list-check me-1"></i>Mes candidatures</a>
        </div>
    <?php endif; ?>

    <!-- Offers Grid -->
    <?php if (count($offers) === 0): ?>
        <div class="text-center py-5 bg-white rounded-4 shadow-sm">
            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
            <h4 class="text-muted">Aucune offre trouvée</h4>
            <p class="text-muted">Essayez de modifier vos critères de recherche ou revenez plus tard.</p>
        </div>
    <?php else: ?>
        <div class="offer-grid">
            <?php foreach ($offers as $offer): ?>
                <?php $isOwner = $canManageOffers && (int)$offer['id_recruteur'] === $userId; $isApplied = in_array((int)$offer['id_offer'], $appliedOfferIds, true); ?>
                <div class="card-offer position-relative">
                    <?php if ($hasImageColumn && !empty($offer['image_path'])): ?>
                        <img class="offer-thumb" src="<?php echo h((string)$offer['image_path']); ?>" alt="<?php echo h($offer['titre']); ?>">
                    <?php else: ?>
                        <div class="offer-thumb d-flex align-items-center justify-content-center text-white fw-bold bg-gradient" style="background: linear-gradient(135deg, #8B5A3A, #C49A6C);">
                            <i class="fas fa-briefcase fa-3x"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="offer-card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <div class="offer-title"><?php echo h($offer['titre']); ?></div>
                                <div class="offer-meta">
                                    <i class="fas fa-user-circle"></i> <?php echo h($offer['prenom'] . ' ' . $offer['nom']); ?>
                                    <?php if ($hasLocationColumn && !empty($offer['location'])): ?>
                                        <span class="mx-1">•</span>
                                        <i class="fas fa-map-marker-alt"></i> <?php echo h($offer['location']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="badge bg-success px-3 py-2 fs-6"><?php echo h((string)$offer['budget']); ?> TND</span>
                        </div>

                        <p class="text-muted mb-3" style="font-size: 0.9rem; line-height: 1.5;"><?php echo nl2br(h(mb_strimwidth((string)$offer['description'], 0, 150, '...'))); ?></p>

                        <div class="mb-3 d-flex flex-wrap gap-2">
                            <span class="offer-chip"><i class="far fa-clock"></i> <?php echo h($offer['duree']); ?></span>
                            <span class="offer-chip"><i class="fas fa-project-diagram"></i> <?php echo h((string)($offer['projet_titre'] ?? 'N/A')); ?></span>
                            <?php if ($hasContactEmailColumn && !empty($offer['contact_email'])): ?>
                                <span class="offer-chip"><i class="fas fa-envelope"></i> Contact disponible</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($hasSkillsColumn && !empty($offer['skills_needed'])): ?>
                            <div class="mb-3">
                                <?php $skills = array_filter(array_map('trim', explode(',', (string)$offer['skills_needed']))); ?>
                                <?php foreach (array_slice($skills, 0, 3) as $skill): ?>
                                    <span class="badge bg-light text-dark border me-1 mb-1 px-3 py-2"><i class="fas fa-check-circle text-success me-1"></i><?php echo h($skill); ?></span>
                                <?php endforeach; ?>
                                <?php if (count($skills) > 3): ?>
                                    <span class="badge bg-light text-dark">+<?php echo count($skills) - 3; ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="offer-actions">
                            <div class="d-flex gap-2">
                                <a class="btn btn-outline-secondary btn-sm" href="offer_details.php?id_offer=<?php echo (int)$offer['id_offer']; ?>"><i class="fas fa-eye"></i> Détails</a>
                                <?php if ($isOwner): ?>
                                    <a class="btn btn-outline-primary btn-sm" href="offres.php?edit=<?php echo (int)$offer['id_offer']; ?>#offerFormCollapse"><i class="fas fa-edit"></i> Modifier</a>
                                    <a class="btn btn-outline-success btn-sm" href="applications.php?id_offer=<?php echo (int)$offer['id_offer']; ?>"><i class="fas fa-users"></i> Candidatures</a>
                                    <form method="post" onsubmit="return confirm('Supprimer cette offre ?');" class="d-inline">
                                        <input type="hidden" name="action" value="delete_offer" />
                                        <input type="hidden" name="id_offer" value="<?php echo (int)$offer['id_offer']; ?>" />
                                        <button class="btn btn-outline-danger btn-sm" type="submit"><i class="fas fa-trash"></i> Supprimer</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php if ($role === 'artisan'): ?>
                                    <?php if ($isApplied): ?>
                                        <span class="badge bg-secondary px-3 py-2"><i class="fas fa-check me-1"></i>Déjà postulée</span>
                                    <?php else: ?>
                                        <a class="btn btn-primary btn-sm" href="apply_offre.php?id_offer=<?php echo (int)$offer['id_offer']; ?>"><i class="fas fa-paper-plane me-1"></i>Postuler</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($role === 'admin' && !$isOwner): ?>
                                    <span class="badge bg-info px-3 py-2"><i class="fas fa-shield-alt me-1"></i>Admin</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav class="mt-5">
            <ul class="pagination justify-content-center pagination-custom">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&q=<?php echo urlencode($search); ?>&competence=<?php echo urlencode($competenceFilter); ?>"><i class="fas fa-chevron-left"></i></a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&q=<?php echo urlencode($search); ?>&competence=<?php echo urlencode($competenceFilter); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&q=<?php echo urlencode($search); ?>&competence=<?php echo urlencode($competenceFilter); ?>"><i class="fas fa-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/scripts.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const syncSkills = () => {
        const values = [...document.querySelectorAll('.skill-check:checked')].map(cb => cb.value);
        const input = document.getElementById('skills_needed_input');
        if (input) input.value = values.join(', ');
    };
    document.querySelectorAll('.skill-check').forEach(cb => cb.addEventListener('change', syncSkills));
    syncSkills();
    
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
});
</script>
</body>
</html>
