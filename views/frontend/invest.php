<?php
// invest.php
// Investment opportunities linked to projects
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/Config.php';
require_once dirname(__DIR__) . '/partials/app_header.php';
require_auth();

$userId = (int)$_SESSION['user_id'];
$userNom = (string)($_SESSION['nom'] ?? 'Utilisateur');
$userPrenom = (string)($_SESSION['prenom'] ?? '');

// Get search parameters
$search = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$minBudget = isset($_GET['min']) ? (int)$_GET['min'] : 0;
$maxBudget = isset($_GET['max']) ? (int)$_GET['max'] : 10000;

// Fetch investment projects
$query = "SELECT id, titre, description, budget_min, status, date_creation
          FROM projet
          WHERE budget_min > 0
          AND status = 'active'";
$params = [];

if ($search !== '') {
    $query .= " AND (titre LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " AND budget_min BETWEEN ? AND ?";
$params[] = $minBudget;
$params[] = $maxBudget;

$query .= " ORDER BY budget_min DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$investments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="حرفة Tunisie - Opportunités d'investissement" />
    <title>Investir | حرفة Tunisie</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="/assets/css/styles.css" rel="stylesheet" />
    <style>
        <?php echo app_header_styles(); ?>
        .legacy-page-navbar { display: none !important; }
        .dashboard-navbar { background-color: rgba(59, 35, 20, 0.95) !important; padding: 1rem 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .dashboard-navbar .nav-link { color: #F5ECD7 !important; font-weight: 600; margin: 0 0.35rem; transition: color .3s; }
        .dashboard-navbar .nav-link:hover { color: #C49A6C !important; }
        .user-greeting { color: #C49A6C; font-weight: bold; margin-right: 1rem; }
        .btn-logout { background: #2E6B3E !important; color: white !important; padding: 0.45rem 0.9rem; border-radius: 4px; text-decoration: none; font-weight: 600; }
        .btn-logout:hover { background: #1d4a2a !important; }
        .hero-banner { background: linear-gradient(135deg, rgba(139, 90, 58, 0.72), rgba(46, 107, 62, 0.72)), url('assets/img/item_pics/IMG_3043.JPG'); background-size: cover; background-position: center; color: #F5ECD7; padding: 4rem 0; text-align: center; border-bottom: 5px solid #8B5A3A; margin-bottom: 2rem; }
        .hero-banner h1 { font-size: 2.4rem; font-weight: bold; margin-bottom: .6rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.25); }
        .hero-banner p { font-size: 1.05rem; margin: 0; }
        .investment-card { background: linear-gradient(135deg, #F5ECD7, #f0e8d0); border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: transform 0.3s; border-left: 5px solid #2E6B3E; }
        .investment-card:hover { transform: translateY(-6px); }
        .status-badge { display: inline-block; background: #2E6B3E; color: white; padding: 0.4rem 0.8rem; border-radius: 4px; font-size: 0.85rem; margin-bottom: 0.5rem; }
        .roi-badge { display: inline-block; background: #FFD700; color: #333; padding: 0.4rem 0.8rem; border-radius: 4px; font-weight: bold; font-size: 0.9rem; }
        .btn-explore { background: #2E6B3E; color: white; padding: 0.8rem 1.5rem; border-radius: 4px; text-decoration: none; display: inline-block; margin: 0.5rem 0; transition: background 0.3s; }
        .btn-explore:hover { background: #8B5A3A; }
        .btn-invest { background: #FFD700; color: #333; padding: 0.8rem 1.5rem; border-radius: 4px; text-decoration: none; display: inline-block; font-weight: bold; transition: background 0.3s; }
        .btn-invest:hover { background: #FFC700; }
        .filter-section { background: #F5ECD7; padding: 1.5rem; margin-bottom: 2rem; border-radius: 8px; }
        .filter-section input, .filter-section select { padding: 0.6rem; margin-right: 0.5rem; border: 1px solid #999; border-radius: 4px; }
        .budget-bar { background: #E0D5C7; height: 6px; border-radius: 3px; margin: 0.5rem 0; overflow: hidden; }
        .budget-fill { background: linear-gradient(to right, #2E6B3E, #8B5A3A); height: 100%; border-radius: 3px; }
        .footer-section { background: #3B2314; color: #F5ECD7; padding: 3rem 0 2rem; margin-top: 3rem; }
        .footer-section a { color: #F5ECD7; text-decoration: none; transition: color 0.3s; }
        .footer-section a:hover { color: #C49A6C; }
    </style>
</head>
<body>
<?php render_app_header('invest'); ?>
<nav class="navbar navbar-expand-lg dashboard-navbar sticky-top legacy-page-navbar">
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
                <li class="nav-item"><a class="nav-link active" href="invest.php"><i class="fas fa-chart-line me-1"></i>Investir</a></li>
                <li class="nav-item"><a class="nav-link" href="offres.php"><i class="fas fa-briefcase me-1"></i>Emplois</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php"><i class="fas fa-user me-1"></i>Profil</a></li>
                <li class="nav-item">
                    <span class="user-greeting">
                        <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars(trim($userPrenom . ' ' . $userNom)); ?>
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
        <h1>Espace Investissement</h1>
        <p>Découvrez et financez des projets artisanaux à fort impact.</p>
    </div>
</section>

<!-- Filter Section -->
<section class="page-section">
    <div class="container">
        <div class="filter-section">
            <h5 style="color: #2E6B3E; margin-bottom: 1rem;">🔍 Filtres de recherche</h5>
            <form method="GET" style="display: grid; grid-template-columns: auto auto auto auto auto; gap: 1rem; align-items: center;">
                <input type="text" name="q" placeholder="Rechercher un projet..." value="<?php echo htmlspecialchars($search); ?>" style="min-width: 200px;" />
                
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <label for="min" style="font-weight: bold; font-size: 0.9rem;">Min (TND):</label>
                    <input type="number" id="min" name="min" placeholder="0" value="<?php echo $minBudget; ?>" style="width: 80px;" />
                </div>
                
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <label for="max" style="font-weight: bold; font-size: 0.9rem;">Max (TND):</label>
                    <input type="number" id="max" name="max" placeholder="10000" value="<?php echo $maxBudget; ?>" style="width: 100px;" />
                </div>
                
                <button type="submit" class="btn-explore" style="margin: 0; padding: 0.6rem 1rem;">Filtrer</button>
                <?php if ($search !== '' || $minBudget > 0 || $maxBudget < 10000): ?>
                    <a href="invest.php" class="btn-explore" style="background: #8B5A3A; margin: 0; padding: 0.6rem 1rem;">Réinitialiser</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (count($investments) === 0): ?>
            <div style="text-align: center; padding: 3rem 0;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">💼</div>
                <h3 style="color: #2E6B3E;">Aucune opportunité d'investissement trouvée</h3>
                <p style="color: #666; font-size: 1.1rem;">Revenez bientôt pour découvrir de nouveaux projets artisanaux.</p>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 2rem;">
                <?php foreach ($investments as $inv): 
                    $maxBudgetDisplay = (int)$inv['budget_min'] + 5000;
                    $roiPercent = 15 + rand(5, 15);
                ?>
                    <div class="investment-card">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                            <div>
                                <div class="status-badge">🟢 Actif</div>
                                <h4 style="color: #2E6B3E; margin-top: 0.5rem;"><?php echo htmlspecialchars((string)$inv['titre']); ?></h4>
                            </div>
                            <div class="roi-badge">ROI: <?php echo $roiPercent; ?>%</div>
                        </div>

                        <p style="color: #555; min-height: 50px; margin: 1rem 0; font-size: 0.95rem;">
                            <?php echo htmlspecialchars(substr((string)$inv['description'], 0, 120)); ?>...
                        </p>

                        <div style="margin: 1rem 0;">
                            <strong style="color: #2E6B3E;">Besoin de financement:</strong>
                            <div class="budget-bar">
                                <div class="budget-fill" style="width: 65%;"></div>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                                <span><strong><?php echo (int)$inv['budget_min']; ?> TND</strong></span>
                                <span style="color: #999;"><?php echo $maxBudgetDisplay; ?> TND (objectif)</span>
                            </div>
                        </div>

                        <div style="background: rgba(255, 215, 0, 0.2); padding: 0.8rem; border-radius: 4px; margin: 1rem 0; text-align: center;">
                            <small><strong>65% financé</strong> • 12 jours restants</small>
                        </div>

                        <hr />

                        <div style="display: flex; justify-content: space-between; gap: 1rem; margin-top: 1rem;">
                            <a class="btn-explore" style="flex: 1; text-align: center; padding: 0.7rem;">Voir le projet</a>
                            <a class="btn-invest" style="flex: 1; text-align: center; padding: 0.7rem;">Investir</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <p style="text-align: center; color: #999; margin-top: 2rem;">Total: <?php echo count($investments); ?> opportunité(s) d'investissement</p>
        <?php endif; ?>
    </div>
</section>

<!-- Info Section -->
<section class="page-section bg-light">
    <div class="container">
        <h3 class="section-heading text-center mb-4">
            <span class="section-heading-upper">Comment</span>
            <span class="section-heading-lower">Investir</span>
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem;">
            <div style="text-align: center;">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">1️⃣</div>
                <h5 style="color: #2E6B3E;">Parcourir les projets</h5>
                <p>Découvrez les projets artisanaux qui vous inspirent</p>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">2️⃣</div>
                <h5 style="color: #2E6B3E;">Sélectionner le montant</h5>
                <p>Choisissez votre niveau d'investissement (TND 100+)</p>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">3️⃣</div>
                <h5 style="color: #2E6B3E;">Valider l'investissement</h5>
                <p>Sécurisez votre investissement et suivez les rendements</p>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">4️⃣</div>
                <h5 style="color: #2E6B3E;">Suivre les retombées</h5>
                <p>Recevez les mises à jour et les bénéfices du projet</p>
            </div>
        </div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/scripts.js"></script>
</body>
</html>
