<?php
// home.php
// Enhanced Main Dashboard for حرفة Tunisie
// Version 3.0 - Enhanced Security, Bug Fixes, Modern UI, Performance Optimizations

declare(strict_types=1);
require_once dirname(__DIR__) . '/config/Config.php';
require_once dirname(__DIR__) . '/views/partials/app_header.php';
require_auth();

$userId = (int)$_SESSION['user_id'];
$userNom = trim($_SESSION['nom'] ?? 'Utilisateur');
$userPrenom = trim($_SESSION['prenom'] ?? '');
$userRole = $_SESSION['role'] ?? 'artisan';
$userEmail = $_SESSION['email'] ?? '';
$baseUrl = rtrim(app_base_url(), '/');

// Enhanced helper functions with better type safety
function has_table(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Table check failed for {$table}: " . $e->getMessage());
        return false;
    }
}

function has_column(PDO $pdo, string $table, string $column): bool
{
    try {
        if (!has_table($pdo, $table)) return false;
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
        $stmt->execute([$column]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Column check failed for {$table}.{$column}: " . $e->getMessage());
        return false;
    }
}

// Cache database schema checks to avoid repeated queries
$schemaCache = [];

function cached_has_table(PDO $pdo, string $table): bool
{
    global $schemaCache;
    $key = "table_{$table}";
    if (!isset($schemaCache[$key])) {
        $schemaCache[$key] = has_table($pdo, $table);
    }
    return $schemaCache[$key];
}

function cached_has_column(PDO $pdo, string $table, string $column): bool
{
    global $schemaCache;
    $key = "column_{$table}_{$column}";
    if (!isset($schemaCache[$key])) {
        $schemaCache[$key] = has_column($pdo, $table, $column);
    }
    return $schemaCache[$key];
}

// Schema checks with caching
$projHasDescription = cached_has_column($pdo, 'projet', 'description');
$projHasBudgetMax = cached_has_column($pdo, 'projet', 'budget_max');
$projHasStatus = cached_has_column($pdo, 'projet', 'status');
$projHasDateCreation = cached_has_column($pdo, 'projet', 'date_creation');
$projHasImagePath = cached_has_column($pdo, 'projet', 'image_path');
$projHasCreator = cached_has_column($pdo, 'projet', 'id_createur');

$offerHasStatus = cached_has_column($pdo, 'offre_emploi', 'status');
$offerHasVerification = cached_has_column($pdo, 'offre_emploi', 'verification_status');
$offerHasLocation = cached_has_column($pdo, 'offre_emploi', 'location');
$offerHasCreatedAt = cached_has_column($pdo, 'offre_emploi', 'created_at');
$offerHasImagePath = cached_has_column($pdo, 'offre_emploi', 'image_path');

$userHasEntreprise = cached_has_column($pdo, 'user', 'entreprise_name');
$userHasAvatar = cached_has_column($pdo, 'user', 'avatar');
$userHasVille = cached_has_column($pdo, 'user', 'ville');
$userHasStatus = cached_has_column($pdo, 'user', 'status');
$userHasEtatCompte = cached_has_column($pdo, 'user', 'etat_compte');
$userHasDateCreation = cached_has_column($pdo, 'user', 'date_creation');

$hasProfileTable = cached_has_table($pdo, 'profil_profetionnel');
$profileHasSpecialite = $hasProfileTable && cached_has_column($pdo, 'profil_profetionnel', 'specialité');
$profileHasBio = $hasProfileTable && cached_has_column($pdo, 'profil_profetionnel', 'bio');
$profileHasExperience = $hasProfileTable && cached_has_column($pdo, 'profil_profetionnel', 'experience');

$appHasMessage = cached_has_column($pdo, 'application', 'message');
$hasNotificationsTable = cached_has_table($pdo, 'notifications');
$hasFormationsTable = cached_has_table($pdo, 'Formations') || cached_has_table($pdo, 'formations');

$formationsTable = null;
if (cached_has_table($pdo, 'Formations')) {
    $formationsTable = 'Formations';
} elseif (cached_has_table($pdo, 'formations')) {
    $formationsTable = 'formations';
}

// Fetch dashboard statistics with better error handling
$stats = [
    'total_projects' => 0,
    'total_offers' => 0,
    'total_artisans' => 0,
    'total_formations' => 0
];

try {
    // Total projects count with status filtering
    $projectWhere = $projHasStatus ? "WHERE status IN ('actif', 'active', 'open', 'published')" : '';
    $projectCountStmt = $pdo->query("SELECT COUNT(*) FROM projet {$projectWhere}");
    $stats['total_projects'] = (int)$projectCountStmt->fetchColumn();
    
    // Total active job offers with verification
    $offerWhereParts = [];
    if ($offerHasStatus) {
        $offerWhereParts[] = "status IN ('published', 'active', 'actif', 'open')";
    }
    if ($offerHasVerification) {
        $offerWhereParts[] = "verification_status = 'verified'";
    }
    $offerWhere = !empty($offerWhereParts) ? 'WHERE ' . implode(' AND ', $offerWhereParts) : '';
    $offerCountStmt = $pdo->query("SELECT COUNT(*) FROM offre_emploi {$offerWhere}");
    $stats['total_offers'] = (int)$offerCountStmt->fetchColumn();
    
    // Total artisans with status check
    $artisanWhere = "role = 'artisan'";
    if ($userHasStatus) {
        $artisanWhere .= " AND status = 'active'";
    } elseif ($userHasEtatCompte) {
        $artisanWhere .= " AND etat_compte = 'actif'";
    }
    $artisanCountStmt = $pdo->query("SELECT COUNT(*) FROM `user` WHERE {$artisanWhere}");
    $stats['total_artisans'] = (int)$artisanCountStmt->fetchColumn();
    
    // Total formations
    if ($hasFormationsTable && $formationsTable !== null) {
        $formationCountStmt = $pdo->query("SELECT COUNT(*) FROM {$formationsTable}");
        $stats['total_formations'] = (int)$formationCountStmt->fetchColumn();
    }
} catch (PDOException $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    // Keep default zeros
}

// Fetch featured projects with proper escaping
$projects = [];
try {
    $projectDescriptionSql = $projHasDescription ? 'description' : "'' AS description";
    $projectBudgetMaxSql = $projHasBudgetMax ? 'budget_max' : 'NULL AS budget_max';
    $projectStatusSql = $projHasStatus ? 'status' : "'open' AS status";
    $projectDateSql = $projHasDateCreation ? 'date_creation' : 'NOW() AS date_creation';
    $projectImageSql = $projHasImagePath ? 'image_path' : "'' AS image_path";
    $projectWhereSql = $projHasStatus ? "WHERE status IN ('actif', 'active', 'open', 'published')" : '';
    $projectOrderSql = $projHasDateCreation ? 'date_creation DESC' : 'id DESC';
    
    $projectsStmt = $pdo->query(
        "SELECT id, titre, {$projectDescriptionSql}, budget_min, {$projectBudgetMaxSql}, 
                {$projectStatusSql}, {$projectDateSql}, {$projectImageSql}
         FROM projet
         {$projectWhereSql}
         ORDER BY {$projectOrderSql}
         LIMIT 6"
    );
    $projects = $projectsStmt->fetchAll() ?: [];
} catch (PDOException $e) {
    error_log("Projects fetch error: " . $e->getMessage());
}

// Fetch formations with error handling
$formations = [];
try {
    if ($hasFormationsTable && $formationsTable !== null) {
        $formStmt = $pdo->query(
            "SELECT id, titre, description, niveau, prix, duree, image_url, formateur
             FROM {$formationsTable}
             WHERE status = 'published' OR status IS NULL
             ORDER BY id DESC
             LIMIT 4"
        );
        $formations = $formStmt->fetchAll() ?: [];
    }
} catch (PDOException $e) {
    error_log("Formations fetch error: " . $e->getMessage());
}

// Fetch job offers with proper joins and error handling
$offers = [];
try {
    $offerLocationSql = $offerHasLocation ? 'o.location' : "'' AS location";
    $offerCreatedAtSql = $offerHasCreatedAt ? 'o.created_at' : 'NOW() AS created_at';
    $offerImageSql = $offerHasImagePath ? 'o.image_path' : "'' AS image_path";
    $offerEntrepriseSql = $userHasEntreprise ? 'u.entreprise_name' : "'' AS entreprise_name";
    
    $offerWhereParts = [];
    if ($offerHasStatus) {
        $offerWhereParts[] = "o.status IN ('published', 'active', 'actif', 'open')";
    }
    if ($offerHasVerification) {
        $offerWhereParts[] = "o.verification_status = 'verified'";
    }
    $offerWhereSql = !empty($offerWhereParts) ? 'WHERE ' . implode(' AND ', $offerWhereParts) : '';
    $offerOrderSql = $offerHasCreatedAt ? 'o.created_at DESC' : 'o.id_offer DESC';
    
    $offersStmt = $pdo->query(
        "SELECT o.id_offer, o.titre, o.description, o.budget, o.duree, {$offerLocationSql}, 
                {$offerCreatedAtSql}, {$offerImageSql},
                u.nom, u.prenom, {$offerEntrepriseSql}
         FROM offre_emploi o
         INNER JOIN `user` u ON o.id_recruteur = u.id_user
         {$offerWhereSql}
         ORDER BY {$offerOrderSql}
         LIMIT 6"
    );
    $offers = $offersStmt->fetchAll() ?: [];
} catch (PDOException $e) {
    error_log("Offers fetch error: " . $e->getMessage());
}

// Fetch artisans with profile info
$artisans = [];
try {
    $artisanAvatarSql = $userHasAvatar ? 'u.avatar' : "'' AS avatar";
    $artisanVilleSql = $userHasVille ? 'u.ville' : "'' AS ville";
    $artisanSpecialiteSql = $hasProfileTable && $profileHasSpecialite ? 'p.specialité' : "'' AS specialite";
    
    $artisanWhere = "u.role = 'artisan'";
    if ($userHasStatus) {
        $artisanWhere .= " AND u.status = 'active'";
    } elseif ($userHasEtatCompte) {
        $artisanWhere .= " AND u.etat_compte = 'actif'";
    }
    $artisanOrderSql = $userHasDateCreation ? 'u.date_creation DESC' : 'u.id_user DESC';
    
    $artisansStmt = $pdo->query(
        "SELECT u.id_user, u.nom, u.prenom, {$artisanAvatarSql}, {$artisanVilleSql}, {$artisanSpecialiteSql}
         FROM `user` u
         LEFT JOIN profil_profetionnel p ON u.id_user = p.id_user
         WHERE {$artisanWhere}
         ORDER BY {$artisanOrderSql}
         LIMIT 4"
    );
    $artisans = $artisansStmt->fetchAll() ?: [];
} catch (PDOException $e) {
    error_log("Artisans fetch error: " . $e->getMessage());
}

// Fetch user-specific data with prepared statements
$userApplications = [];
$userOffers = [];
$userProjects = [];
$notifications = [];
$unreadNotificationCount = 0;

if ($userRole === 'artisan') {
    // Fetch artisan's applications
    try {
        $appMessageSql = $appHasMessage ? 'a.message' : "'' AS message";
        $appEntrepriseSql = $userHasEntreprise ? 'u.entreprise_name' : "'' AS entreprise_name";
        
        $appStmt = $pdo->prepare(
            "SELECT a.id, a.status, a.date_creation, {$appMessageSql},
                    o.titre AS offre_titre, o.budget, u.nom, u.prenom, {$appEntrepriseSql}
             FROM application a
             INNER JOIN application_offre ao ON a.id = ao.id_application
             INNER JOIN offre_emploi o ON ao.id_offre = o.id_offer
             INNER JOIN `user` u ON o.id_recruteur = u.id_user
             WHERE a.id_user = ?
             ORDER BY a.date_creation DESC
             LIMIT 5"
        );
        $appStmt->execute([$userId]);
        $userApplications = $appStmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        error_log("User applications fetch error: " . $e->getMessage());
    }
    
} elseif ($userRole === 'recruteur' || $userRole === 'entrepreneur') {
    // Fetch recruiter's job offers
    try {
        $recruiterOfferStatusSql = $offerHasStatus ? 'o.status' : "'active' AS status";
        $recruiterOfferCreatedSql = $offerHasCreatedAt ? 'o.created_at' : 'NOW() AS created_at';
        $recruiterOfferOrderSql = $offerHasCreatedAt ? 'o.created_at DESC' : 'o.id_offer DESC';
        
        $offStmt = $pdo->prepare(
            "SELECT o.id_offer, o.titre, o.budget, o.duree, {$recruiterOfferStatusSql}, 
                    {$recruiterOfferCreatedSql},
                    COUNT(DISTINCT ao.id_application) AS app_count,
                    COUNT(DISTINCT CASE WHEN a.status = 'pending' THEN a.id END) AS pending_count
             FROM offre_emploi o
             LEFT JOIN application_offre ao ON o.id_offer = ao.id_offre
             LEFT JOIN application a ON ao.id_application = a.id
             WHERE o.id_recruteur = ?
             GROUP BY o.id_offer
             ORDER BY {$recruiterOfferOrderSql}
             LIMIT 5"
        );
        $offStmt->execute([$userId]);
        $userOffers = $offStmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        error_log("User offers fetch error: " . $e->getMessage());
    }
    
    // Fetch recruiter's projects
    if ($projHasCreator) {
        try {
            $userProjBudgetMaxSql = $projHasBudgetMax ? 'budget_max' : 'NULL AS budget_max';
            $userProjStatusSql = $projHasStatus ? 'status' : "'open' AS status";
            $userProjDateSql = $projHasDateCreation ? 'date_creation' : 'NOW() AS date_creation';
            $userProjOrderSql = $projHasDateCreation ? 'date_creation DESC' : 'id DESC';
            
            $projStmt = $pdo->prepare(
                "SELECT id, titre, budget_min, {$userProjBudgetMaxSql}, {$userProjStatusSql}, 
                        {$userProjDateSql}
                 FROM projet
                 WHERE id_createur = ?
                 ORDER BY {$userProjOrderSql}
                 LIMIT 5"
            );
            $projStmt->execute([$userId]);
            $userProjects = $projStmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log("User projects fetch error: " . $e->getMessage());
        }
    }
}

// Fetch notifications for every authenticated role
if ($hasNotificationsTable) {
    try {
        $notifCountStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM notifications
             WHERE user_id = ? AND is_read = 0"
        );
        $notifCountStmt->execute([$userId]);
        $unreadNotificationCount = (int)$notifCountStmt->fetchColumn();

        $notifStmt = $pdo->prepare(
            "SELECT id, title, message, type, is_read, created_at
             FROM notifications
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT 5"
        );
        $notifStmt->execute([$userId]);
        $notifications = $notifStmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log("Notifications fetch error: " . $e->getMessage());
    }
}

// Helper functions
function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function formatCurrency($amount): string
{
    $floatAmount = is_numeric($amount) ? (float)$amount : 0;
    return number_format($floatAmount, 0, '.', ' ') . ' TND';
}

function truncateText(string $text, int $length = 100, string $ellipsis = '...'): string
{
    $text = trim($text);
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $ellipsis;
}

function getStatusBadgeClass(string $status): string
{
    return match (strtolower($status)) {
        'accepted', 'approved', 'verified' => 'success',
        'pending', 'submitted', 'reviewed' => 'warning',
        'rejected', 'cancelled' => 'danger',
        'active', 'published', 'open' => 'success',
        'draft' => 'secondary',
        default => 'info'
    };
}

function getRelativeTime(string $datetime): string
{
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'à l\'instant';
    if ($diff < 3600) return 'il y a ' . floor($diff / 60) . ' min';
    if ($diff < 86400) return 'il y a ' . floor($diff / 3600) . ' h';
    if ($diff < 604800) return 'il y a ' . floor($diff / 86400) . ' j';
    
    return date('d/m/Y', $timestamp);
}

function resolveOfferImageUrl(?string $imagePath): string
{
    $path = trim((string)$imagePath);
    if ($path === '') {
        return '';
    }
    
    if (preg_match('#^(https?:)?//#i', $path) === 1 || str_starts_with($path, 'data:')) {
        return $path;
    }
    
    $path = str_replace(['\\\\', '\\'], '/', $path);
    if (str_starts_with($path, '/')) {
        return $path;
    }
    
    $path = ltrim($path, './');
    if (str_starts_with($path, 'uploads/')) {
        return '/herfa/controllers/offer_emploi/' . $path;
    }
    
    return '/herfa/' . ltrim($path, '/');
}

// Generate CSRF token for forms if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Get user initials for avatar placeholder
$userInitials = strtoupper(mb_substr($userPrenom, 0, 1) . mb_substr($userNom, 0, 1));

// Build personalized quick actions and snapshot cards
$quickActions = [];
$snapshotCards = [];

if ($userRole === 'artisan') {
    $pendingApplications = 0;
    foreach ($userApplications as $application) {
        if (($application['status'] ?? '') === 'pending') {
            $pendingApplications++;
        }
    }

    $quickActions[] = [
        'title' => "Trouver une mission",
        'description' => "Parcourez les offres publiees et postulez en quelques clics.",
        'icon' => 'fas fa-briefcase',
        'url' => $baseUrl . '/controllers/offer_emploi/offres.php'
    ];
    $quickActions[] = [
        'title' => "Mon profil",
        'description' => "Renforcez votre profil pour augmenter votre visibilite.",
        'icon' => 'fas fa-user-check',
        'url' => $baseUrl . '/controllers/user/profile.php'
    ];
    $quickActions[] = [
        'title' => "Developper mes competences",
        'description' => "Suivez des formations adaptees a votre niveau.",
        'icon' => 'fas fa-graduation-cap',
        'url' => $baseUrl . '/controllers/formation/formations.php'
    ];

    $snapshotCards[] = [
        'label' => 'Candidatures',
        'value' => (int)count($userApplications),
        'hint' => 'envoyees'
    ];
    $snapshotCards[] = [
        'label' => 'En attente',
        'value' => (int)$pendingApplications,
        'hint' => 'a suivre'
    ];
} elseif ($userRole === 'recruteur' || $userRole === 'entrepreneur') {
    $pendingCandidates = 0;
    foreach ($userOffers as $offer) {
        $pendingCandidates += (int)($offer['pending_count'] ?? 0);
    }

    $quickActions[] = [
        'title' => "Publier une offre",
        'description' => "Attirez les bons profils avec une annonce claire et complete.",
        'icon' => 'fas fa-plus-circle',
        'url' => $baseUrl . '/controllers/offer_emploi/create_offre.php'
    ];
    $quickActions[] = [
        'title' => "Suivre mes candidatures",
        'description' => "Priorisez les profils en attente et accelerez vos recrutements.",
        'icon' => 'fas fa-user-clock',
        'url' => $baseUrl . '/controllers/offer_emploi/recruiter_dashboard.php'
    ];
    $quickActions[] = [
        'title' => "Lancer un projet",
        'description' => "Transformez vos idees en projets visibles par les investisseurs.",
        'icon' => 'fas fa-lightbulb',
        'url' => $baseUrl . '/controllers/projects/create_project.php'
    ];

    $snapshotCards[] = [
        'label' => 'Mes offres',
        'value' => (int)count($userOffers),
        'hint' => 'publiees'
    ];
    $snapshotCards[] = [
        'label' => 'Candidats',
        'value' => (int)$pendingCandidates,
        'hint' => 'en attente'
    ];
    $snapshotCards[] = [
        'label' => 'Mes projets',
        'value' => (int)count($userProjects),
        'hint' => 'actifs'
    ];
} else {
    $quickActions[] = [
        'title' => "Explorer les projets",
        'description' => "Decouvrez les projets qui font bouger la communaute.",
        'icon' => 'fas fa-project-diagram',
        'url' => $baseUrl . '/controllers/projects/projets.php'
    ];
    $quickActions[] = [
        'title' => "Voir les offres",
        'description' => "Consultez les opportunites disponibles sur la plateforme.",
        'icon' => 'fas fa-briefcase',
        'url' => $baseUrl . '/controllers/offer_emploi/offres.php'
    ];
    $quickActions[] = [
        'title' => "Completer mon profil",
        'description' => "Ajoutez vos informations pour mieux vous connecter aux autres.",
        'icon' => 'fas fa-user-edit',
        'url' => $baseUrl . '/controllers/user/profile.php'
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="حرفة Tunisie - Plateforme dédiée à l'artisanat tunisien : connectez artisans, recruteurs et investisseurs" />
    <meta name="theme-color" content="#2E6B3E" />
    <meta name="csrf-token" content="<?php echo $csrfToken; ?>" />
    <title>Accueil | حرفة Tunisie</title>
    <link rel="icon" type="image/x-icon" href="<?php echo h($baseUrl . '/public/assets/favicon.ico'); ?>" />
    
    <!-- Preconnect to CDNs for performance -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" />
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" />
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <!-- Custom CSS -->
    <link href="<?php echo h($baseUrl . '/public/assets/css/styles.css'); ?>" rel="stylesheet" />
    
    <style>
        :root {
            --primary-brown: #8B5A3A;
            --primary-green: #2E6B3E;
            --cream: #F5ECD7;
            --dark-brown: #3B2314;
            --light-cream: #FAF7F0;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.06);
            --shadow-md: 0 5px 20px rgba(0,0,0,0.08);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.12);
            --transition-fast: 0.2s ease;
            --transition-normal: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: var(--light-cream);
            font-family: 'Segoe UI', system-ui, -apple-system, 'BlinkMacSystemFont', 'Roboto', sans-serif;
            scroll-behavior: smooth;
            overflow-x: hidden;
        }

        /* Scroll Progress Bar */
        .scroll-progress {
            position: fixed;
            top: 0;
            left: 0;
            height: 4px;
            width: 0%;
            z-index: 2000;
            background: linear-gradient(90deg, var(--primary-green), #C49A6C, var(--primary-brown));
            box-shadow: 0 2px 10px rgba(46, 107, 62, 0.45);
            transition: width 0.05s linear;
        }
        
        /* Navbar Styles */
        .dashboard-navbar {
            background: rgba(59, 35, 20, 0.96) !important;
            backdrop-filter: blur(10px);
            padding: 0.8rem 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: all var(--transition-normal);
        }
        .legacy-page-navbar { display: none !important; }
        
        .dashboard-navbar.scrolled {
            padding: 0.5rem 0;
            background: rgba(59, 35, 20, 0.98) !important;
        }
        
        .dashboard-navbar .nav-link {
            color: var(--cream) !important;
            font-weight: 500;
            margin: 0 0.3rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all var(--transition-fast);
            position: relative;
        }
        
        .dashboard-navbar .nav-link:hover {
            background: rgba(197, 154, 108, 0.2);
            color: #C49A6C !important;
            transform: translateY(-2px);
        }
        
        .dashboard-navbar .nav-link.active {
            background: var(--primary-green);
            color: white !important;
        }
        
        .user-greeting {
            background: rgba(197, 154, 108, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 40px;
            color: var(--cream);
            font-weight: 500;
            margin-right: 0.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .user-avatar-small {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary-green);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .btn-logout {
            background: #DC3545 !important;
            color: white !important;
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all var(--transition-fast);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
        }
        
        .btn-logout:hover {
            background: #c82333 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220,53,69,0.3);
        }
        
        /* Hero Section */
        .hero-banner {
            background: linear-gradient(135deg, rgba(139, 90, 58, 0.85), rgba(46, 107, 62, 0.85)), 
                        url('<?php echo h($baseUrl . '/public/assets/img/item_pics/IMG_3043.JPG'); ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: var(--cream);
            padding: 5rem 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        @media (max-width: 991px) {
            .hero-banner {
                background-attachment: scroll;
            }
        }

        .hero-banner::before {
            content: '';
            position: absolute;
            inset: -35% -25%;
            background: radial-gradient(circle at 20% 30%, rgba(245, 236, 215, 0.24), transparent 45%),
                        radial-gradient(circle at 80% 70%, rgba(196, 154, 108, 0.3), transparent 48%);
            animation: heroDrift 10s ease-in-out infinite alternate;
            z-index: 0;
            pointer-events: none;
        }

        .hero-canvas {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            opacity: 0.45;
            pointer-events: none;
        }

        .hero-glow {
            position: absolute;
            width: 340px;
            height: 340px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(245, 236, 215, 0.34) 0%, rgba(245, 236, 215, 0) 68%);
            filter: blur(2px);
            top: 10%;
            left: 50%;
            transform: translateX(-50%);
            pointer-events: none;
            z-index: 1;
            transition: transform 0.2s linear;
        }

        .hero-banner .container {
            position: relative;
            z-index: 2;
        }

        .hero-kicker {
            display: inline-block;
            margin-bottom: 1rem;
            padding: 0.35rem 1rem;
            border-radius: 999px;
            background: rgba(245, 236, 215, 0.2);
            border: 1px solid rgba(245, 236, 215, 0.4);
            font-size: 0.82rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            animation: fadeInUp 0.8s ease;
        }

        .hero-highlight {
            background: linear-gradient(90deg, #F5ECD7, #E8D2A5, #F5ECD7);
            background-size: 220% 100%;
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: shineText 4.5s linear infinite;
        }

        .hero-badge {
            position: absolute;
            z-index: 2;
            padding: 0.55rem 1rem;
            border-radius: 999px;
            background: rgba(245, 236, 215, 0.14);
            border: 1px solid rgba(245, 236, 215, 0.45);
            color: #fff;
            font-size: 0.84rem;
            font-weight: 600;
            backdrop-filter: blur(5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            animation: floatBadge 3.8s ease-in-out infinite;
        }

        .hero-badge.badge-left {
            left: 7%;
            bottom: 18%;
        }

        .hero-badge.badge-right {
            right: 7%;
            top: 20%;
            animation-delay: 0.5s;
        }
        
        .hero-banner h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.3);
            animation: fadeInUp 0.8s ease;
        }
        
        .hero-banner p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.95;
            animation: fadeInUp 0.8s ease 0.2s both;
        }
        
        .btn-cta {
            background: var(--primary-green);
            color: white;
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 50px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem;
            font-weight: 600;
            transition: all var(--transition-fast);
            animation: fadeInUp 0.8s ease 0.4s both;
        }
        
        .btn-cta:hover {
            background: var(--primary-brown);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            color: white;
        }
        
        .btn-outline-cta {
            background: transparent;
            border: 2px solid var(--cream);
            color: var(--cream);
        }
        
        .btn-outline-cta:hover {
            background: var(--cream);
            color: var(--primary-green);
            border-color: var(--cream);
        }

        @media (max-width: 768px) {
            .hero-badge {
                display: none;
            }
            .hero-banner h1 {
                font-size: 2rem;
            }
            .hero-banner p {
                font-size: 1rem;
            }
        }

        /* Stats Section */
        .stats-section {
            background: white;
            padding: 3rem 0;
            margin-top: -2rem;
            position: relative;
            z-index: 2;
            border-radius: 30px 30px 0 0;
            box-shadow: 0 -10px 30px rgba(0,0,0,0.05);
        }
        
        .stat-card {
            text-align: center;
            padding: 1.5rem;
            transition: all var(--transition-normal);
            border-radius: 16px;
            background: white;
        }

        .stat-card:hover {
            background: #fffdf9;
            transform: translateY(-6px);
            box-shadow: var(--shadow-md);
        }
        
        .stat-card i {
            font-size: 2.5rem;
            color: var(--primary-green);
            margin-bottom: 1rem;
        }
        
        .stat-number {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--dark-brown);
            margin-bottom: 0.3rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Quick Actions */
        .quick-actions-section {
            padding: 2rem 0 1rem;
        }

        .quick-actions-shell {
            background: white;
            border-radius: 22px;
            box-shadow: var(--shadow-sm);
            padding: 1.5rem;
        }

        .quick-actions-title {
            color: var(--primary-green);
            font-size: 1.45rem;
            font-weight: 700;
            margin: 0;
        }

        .quick-actions-subtitle {
            color: #6a6a6a;
            margin: 0.4rem 0 0;
            font-size: 0.95rem;
        }

        .role-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.8rem;
            border-radius: 999px;
            background: #f3f8f4;
            color: var(--primary-green);
            border: 1px solid rgba(46, 107, 62, 0.2);
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .action-tile {
            background: linear-gradient(145deg, #ffffff, #fcfaf6);
            border: 1px solid #efe6d7;
            border-radius: 16px;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 0.9rem;
            text-decoration: none;
            color: inherit;
            min-height: 118px;
            transition: transform var(--transition-fast), box-shadow var(--transition-fast), border-color var(--transition-fast);
        }

        .action-tile:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: #e0c69f;
            color: inherit;
        }

        .action-tile-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary-green), #3f8b55);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .action-tile-content h3 {
            margin: 0;
            color: var(--dark-brown);
            font-size: 1rem;
            font-weight: 700;
        }

        .action-tile-content p {
            margin: 0.35rem 0 0;
            color: #6f6f6f;
            font-size: 0.86rem;
            line-height: 1.45;
        }

        .action-tile-arrow {
            margin-left: auto;
            color: var(--primary-brown);
            opacity: 0.75;
        }

        .snapshot-grid {
            margin-top: 1rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 0.8rem;
        }

        .snapshot-chip {
            background: #fffaf0;
            border: 1px solid #f0e2cb;
            border-radius: 14px;
            padding: 0.75rem 0.8rem;
            text-align: center;
        }

        .snapshot-value {
            color: var(--primary-green);
            font-size: 1.3rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .snapshot-label {
            color: var(--dark-brown);
            font-size: 0.8rem;
            margin-top: 0.2rem;
            font-weight: 700;
        }

        .snapshot-hint {
            color: #7d7d7d;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.45px;
        }
        
        /* Section Styles */
        .section-header {
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 2rem;
            color: var(--primary-green);
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            display: inline-block;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--primary-brown);
            border-radius: 3px;
        }
        
        .section-subtitle {
            color: var(--primary-brown);
            margin-bottom: 0;
            font-size: 1rem;
        }
        
        /* Card Styles */
        .project-card, .formation-card, .offer-card, .artisan-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-normal);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .project-card:hover, .formation-card:hover, .offer-card:hover, .artisan-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }

        .reveal-on-scroll {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity 0.7s ease, transform 0.7s ease;
        }

        .reveal-on-scroll.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        .card-img-top {
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, var(--primary-brown), #C49A6C);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }
        
        .card-img-top img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .card-body {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-green);
            margin-bottom: 0.5rem;
        }
        
        .card-text {
            color: #666;
            font-size: 0.85rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }
        
        .badge-custom {
            background: var(--cream);
            color: var(--primary-brown);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .progress-custom {
            background: #E0D5C7;
            height: 6px;
            border-radius: 3px;
            overflow: hidden;
            margin: 1rem 0;
        }
        
        .progress-custom-bar {
            background: linear-gradient(90deg, var(--primary-green), var(--primary-brown));
            height: 100%;
            border-radius: 3px;
            transition: width 0.5s ease;
        }
        
        /* User Section */
        .user-section {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin: 2rem 0;
            box-shadow: var(--shadow-sm);
        }
        
        .section-header-custom {
            border-bottom: 2px solid var(--cream);
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .notification-item {
            background: var(--cream);
            border-left: 4px solid var(--primary-green);
            padding: 1rem;
            margin-bottom: 0.8rem;
            border-radius: 10px;
            transition: all var(--transition-fast);
        }
        
        .notification-item:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow-sm);
        }
        
        .notification-unread {
            background: #FFF8E7;
            border-left-color: var(--primary-brown);
        }
        
        .application-status {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-pending { background: #FFF3CD; color: #856404; }
        .status-accepted { background: #D4EDDA; color: #155724; }
        .status-rejected { background: #F8D7DA; color: #721C24; }
        .status-reviewed { background: #D1ECF1; color: #0C5460; }
        
        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--primary-green), var(--primary-brown));
            color: white;
            padding: 4rem 0;
            text-align: center;
            margin: 3rem 0;
            border-radius: 30px;
        }
        
        .cta-section h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .btn-light-cta {
            background: white;
            color: var(--primary-green);
            padding: 0.8rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all var(--transition-fast);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-light-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            color: var(--primary-brown);
        }
        
        .btn-outline-light-cta {
            background: transparent;
            border: 2px solid white;
            color: white;
        }
        
        .btn-outline-light-cta:hover {
            background: white;
            color: var(--primary-green);
        }
        
        /* Footer */
        .footer-section {
            background: var(--dark-brown);
            color: var(--cream);
            padding: 3rem 0 2rem;
            margin-top: 3rem;
        }
        
        .footer-section a {
            color: var(--cream);
            text-decoration: none;
            transition: color var(--transition-fast);
        }
        
        .footer-section a:hover {
            color: #C49A6C;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 20px;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
        }
        
        /* Loading Skeleton */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes shineText {
            0% { background-position: 0% 50%; }
            100% { background-position: 100% 50%; }
        }

        @keyframes floatBadge {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        @keyframes heroDrift {
            0% { transform: translate3d(-1%, -1%, 0) scale(1); }
            100% { transform: translate3d(1%, 1.5%, 0) scale(1.05); }
        }
        
        .fade-in {
            animation: fadeInUp 0.6s ease forwards;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .section-title { font-size: 1.5rem; }
            .stat-number { font-size: 1.5rem; }
            .cta-section h2 { font-size: 1.5rem; }
            .cta-section { padding: 2rem 1rem; }
            .user-greeting span { display: none; }
            .quick-actions-shell { padding: 1rem; }
            .action-tile { min-height: 102px; }
        }
        
        /* Toast Notifications */
        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 280px;
            animation: slideInRight 0.3s ease;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>

<div class="scroll-progress" id="scrollProgress" aria-hidden="true"></div>

<!-- Toast Container for notifications -->
<div class="toast-notification" id="toastContainer"></div>

<!-- NAVBAR -->
<?php render_app_header('home'); ?>
<nav class="navbar navbar-expand-lg dashboard-navbar sticky-top legacy-page-navbar" id="legacyNavbar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo h($baseUrl . '/controllers/home.php'); ?>">
            <img src="<?php echo h($baseUrl . '/public/assets/img/logo_herfa.png'); ?>" alt="Logo حرفة" height="40" style="margin-right: 0.8rem;">
            <span style="color: var(--cream); font-weight: 700; font-size: 1.3rem;">حرفة Tunisie</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Menu de navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item"><a class="nav-link active" href="<?php echo h($baseUrl . '/controllers/home.php'); ?>"><i class="fas fa-home me-1"></i>Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo h($baseUrl . '/controllers/projects/projets.php'); ?>"><i class="fas fa-project-diagram me-1"></i>Projets</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo h($baseUrl . '/controllers/formation/formations.php'); ?>"><i class="fas fa-graduation-cap me-1"></i>Formations</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo h($baseUrl . '/controllers/invester/invest.php'); ?>"><i class="fas fa-chart-line me-1"></i>Investir</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo h($baseUrl . '/controllers/offer_emploi/offres.php'); ?>"><i class="fas fa-briefcase me-1"></i>Emplois</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo h($baseUrl . '/controllers/user/profile.php'); ?>"><i class="fas fa-user me-1"></i>Profil</a></li>
                <li class="nav-item">
                    <span class="user-greeting">
                        <span class="user-avatar-small"><?php echo h($userInitials); ?></span>
                        <span><?php echo h(trim($userPrenom . ' ' . $userNom)); ?></span>
                    </span>
                </li>
                <li class="nav-item">
                    <form action="<?php echo h($baseUrl . '/controllers/session_status.php'); ?>" method="POST" class="d-inline">
                        <input type="hidden" name="action" value="logout">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <button type="submit" class="btn-logout">
                            <i class="fas fa-sign-out-alt"></i> Déconnexion
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- HERO SECTION -->
<section class="hero-banner">
    <canvas class="hero-canvas" id="heroCanvas" aria-hidden="true"></canvas>
    <div class="hero-glow" id="heroGlow" aria-hidden="true"></div>
    <div class="hero-badge badge-left"><i class="fas fa-rocket me-2"></i><?php echo (int)$stats['total_projects']; ?> projets actifs</div>
    <div class="hero-badge badge-right"><i class="fas fa-users me-2"></i><?php echo (int)$stats['total_artisans']; ?> artisans inscrits</div>
    <div class="container">
        <div class="hero-kicker">Plateforme premium • Talents • Investisseurs</div>
        <h1><span class="hero-highlight">L'artisanat tunisien</span>,<br>à l'ère du numérique</h1>
        <p>Une plateforme qui connecte les créateurs, les artisans et les investisseurs pour faire vivre l'artisanat tunisien.</p>
        <div>
            <a href="<?php echo h($baseUrl . '/controllers/projects/projets.php'); ?>" class="btn-cta"><i class="fas fa-rocket me-2"></i>Explorer les projets</a>
            <a href="<?php echo h($baseUrl . '/controllers/user/profile.php'); ?>" class="btn-cta btn-outline-cta"><i class="fas fa-user-edit me-2"></i>Compléter mon profil</a>
        </div>
    </div>
</section>

<!-- STATISTICS SECTION -->
<section class="stats-section">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <i class="fas fa-project-diagram"></i>
                    <div class="stat-number" data-counter-target="<?php echo (int)$stats['total_projects']; ?>">0</div>
                    <div class="stat-label">Projets actifs</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <i class="fas fa-briefcase"></i>
                    <div class="stat-number" data-counter-target="<?php echo (int)$stats['total_offers']; ?>">0</div>
                    <div class="stat-label">Offres d'emploi</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <div class="stat-number" data-counter-target="<?php echo (int)$stats['total_artisans']; ?>">0</div>
                    <div class="stat-label">Artisans inscrits</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <i class="fas fa-book-open"></i>
                    <div class="stat-number" data-counter-target="<?php echo (int)$stats['total_formations']; ?>">0</div>
                    <div class="stat-label">Formations</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- QUICK ACTIONS -->
<section class="quick-actions-section">
    <div class="container">
        <div class="quick-actions-shell reveal-on-scroll">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                    <h2 class="quick-actions-title">Vos prochaines etapes</h2>
                    <p class="quick-actions-subtitle">Un acces rapide aux actions les plus utiles pour votre espace.</p>
                </div>
                <span class="role-pill"><i class="fas fa-compass"></i><?php echo h($userRole); ?></span>
            </div>

            <div class="row g-3">
                <?php foreach ($quickActions as $action): ?>
                    <div class="col-md-4">
                        <a href="<?php echo h($action['url']); ?>" class="action-tile">
                            <span class="action-tile-icon"><i class="<?php echo h($action['icon']); ?>"></i></span>
                            <span class="action-tile-content">
                                <h3><?php echo h($action['title']); ?></h3>
                                <p><?php echo h($action['description']); ?></p>
                            </span>
                            <i class="fas fa-arrow-right action-tile-arrow" aria-hidden="true"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($snapshotCards)): ?>
                <div class="snapshot-grid">
                    <?php foreach ($snapshotCards as $snapshot): ?>
                        <div class="snapshot-chip">
                            <div class="snapshot-value"><?php echo (int)$snapshot['value']; ?></div>
                            <div class="snapshot-label"><?php echo h($snapshot['label']); ?></div>
                            <div class="snapshot-hint"><?php echo h($snapshot['hint']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="container mt-5">
    
    <!-- NOTIFICATIONS SECTION -->
    <?php if (!empty($notifications)): ?>
    <div class="user-section reveal-on-scroll">
        <div class="d-flex justify-content-between align-items-center section-header-custom">
            <h4 class="mb-0">
                <i class="fas fa-bell text-success me-2"></i>Notifications
                <?php if ($unreadNotificationCount > 0): ?>
                    <span class="badge bg-danger ms-2"><?php echo $unreadNotificationCount; ?> nouvelle(s)</span>
                <?php endif; ?>
            </h4>
            <a href="<?php echo h($baseUrl . '/controllers/notifications.php'); ?>" class="btn btn-sm btn-link text-success">Voir tout</a>
        </div>
        <?php foreach ($notifications as $notif): ?>
            <div class="notification-item <?php echo $notif['is_read'] ? '' : 'notification-unread'; ?>">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <strong><?php echo h($notif['title']); ?></strong>
                        <p class="mb-0 small text-muted"><?php echo h($notif['message']); ?></p>
                    </div>
                    <small class="text-muted ms-2"><?php echo getRelativeTime($notif['created_at']); ?></small>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- FEATURED PROJECTS SECTION -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4 section-header">
        <div>
            <h2 class="section-title">Projets à la une</h2>
            <p class="section-subtitle">Découvrez les projets innovants de notre communauté</p>
        </div>
        <a href="<?php echo h($baseUrl . '/controllers/projects/projets.php'); ?>" class="btn btn-outline-success rounded-pill"><i class="fas fa-arrow-right me-2"></i>Voir tous</a>
    </div>
    
    <?php if (count($projects) > 0): ?>
        <div class="row g-4 mb-5">
            <?php foreach ($projects as $proj): 
                $funding = rand(40, 95); // Temporary - should be calculated from actual investments
                $imagePath = !empty($proj['image_path']) ? h($proj['image_path']) : '';
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="project-card reveal-on-scroll">
                        <div class="card-img-top">
                            <?php if ($imagePath): ?>
                                <img src="<?php echo $imagePath; ?>" alt="<?php echo h($proj['titre']); ?>" loading="lazy">
                            <?php else: ?>
                                <i class="fas fa-hands-helping fa-3x text-white"></i>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="card-title"><?php echo h($proj['titre']); ?></div>
                            <div class="badge-custom mb-2">
                                <i class="fas fa-tag"></i> Budget: <?php echo formatCurrency($proj['budget_min']); ?>
                            </div>
                            <p class="card-text"><?php echo h(truncateText((string)$proj['description'], 100)); ?></p>
                            <div class="progress-custom">
                                <div class="progress-custom-bar" style="width: <?php echo $funding; ?>%"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <small class="text-muted"><i class="far fa-calendar-alt me-1"></i><?php echo date('d/m/Y', strtotime($proj['date_creation'])); ?></small>
                                <a href="<?php echo h($baseUrl . '/controllers/projects/projet_details.php?id=' . $proj['id']); ?>" class="btn btn-sm btn-success rounded-pill">En savoir plus <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state mb-5">
            <i class="fas fa-inbox"></i>
            <p class="text-muted mb-3">Aucun projet actuellement. Soyez le premier à en créer un !</p>
            <a href="<?php echo h($baseUrl . '/controllers/projects/create_project.php'); ?>" class="btn btn-success rounded-pill"><i class="fas fa-plus me-2"></i>Créer un projet</a>
        </div>
    <?php endif; ?>
    
    <!-- FORMATIONS SECTION -->
    <?php if (count($formations) > 0): ?>
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4 mt-5 section-header">
        <div>
            <h2 class="section-title">Formations populaires</h2>
            <p class="section-subtitle">Développez vos compétences avec nos experts</p>
        </div>
        <a href="<?php echo h($baseUrl . '/controllers/formation/formations.php'); ?>" class="btn btn-outline-success rounded-pill"><i class="fas fa-arrow-right me-2"></i>Voir toutes</a>
    </div>
    
    <div class="row g-4 mb-5">
        <?php foreach ($formations as $form): ?>
            <div class="col-md-6 col-lg-3">
                <div class="formation-card reveal-on-scroll">
                    <div class="card-img-top d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, var(--primary-brown), #C49A6C);">
                        <?php if (!empty($form['image_url'])): ?>
                            <img src="<?php echo h($form['image_url']); ?>" alt="<?php echo h($form['titre']); ?>" loading="lazy">
                        <?php else: ?>
                            <i class="fas fa-chalkboard-teacher fa-3x text-white"></i>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="card-title"><?php echo h($form['titre']); ?></div>
                        <div class="badge-custom mb-2">
                            <i class="fas fa-level-up-alt"></i> <?php echo h($form['niveau'] ?? 'Débutant'); ?>
                        </div>
                        <div class="mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star text-warning"></i>
                            <?php endfor; ?>
                            <small class="text-muted">(4.8)</small>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-auto">
                            <strong class="text-success"><?php echo formatCurrency($form['prix']); ?></strong>
                            <a href="<?php echo h($baseUrl . '/controllers/formation/formation_details.php?id=' . $form['id']); ?>" class="btn btn-sm btn-outline-success rounded-pill">S'inscrire</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- JOB OFFERS SECTION -->
    <?php if (count($offers) > 0): ?>
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4 mt-5 section-header">
        <div>
            <h2 class="section-title">Offres d'emploi</h2>
            <p class="section-subtitle">Trouvez l'opportunité qui correspond à vos talents</p>
        </div>
        <a href="<?php echo h($baseUrl . '/controllers/offer_emploi/offres.php'); ?>" class="btn btn-outline-success rounded-pill"><i class="fas fa-arrow-right me-2"></i>Voir toutes</a>
    </div>
    
    <div class="row g-4 mb-5">
        <?php foreach ($offers as $offer): ?>
            <?php $offerImageUrl = resolveOfferImageUrl((string)($offer['image_path'] ?? '')); ?>
            <div class="col-md-6 col-lg-4">
                <div class="offer-card reveal-on-scroll">
                    <div class="card-img-top d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, var(--primary-green), var(--primary-brown));">
                        <?php if ($offerImageUrl !== ''): ?>
                            <img src="<?php echo h($offerImageUrl); ?>" alt="<?php echo h($offer['titre']); ?>" loading="lazy">
                        <?php else: ?>
                            <i class="fas fa-briefcase fa-3x text-white"></i>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="card-title"><?php echo h($offer['titre']); ?></div>
                        <div class="d-flex gap-2 mb-2 flex-wrap">
                            <span class="badge-custom"><i class="fas fa-building"></i> <?php echo h($offer['entreprise_name'] ?? $offer['prenom'] . ' ' . $offer['nom']); ?></span>
                            <?php if (!empty($offer['location'])): ?>
                                <span class="badge-custom"><i class="fas fa-map-marker-alt"></i> <?php echo h($offer['location']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="badge bg-success mb-2"><?php echo formatCurrency($offer['budget']); ?></div>
                        <p class="card-text"><?php echo h(truncateText((string)$offer['description'], 80)); ?></p>
                        <div class="d-flex justify-content-between align-items-center mt-auto">
                            <small class="text-muted"><i class="far fa-clock"></i> <?php echo h($offer['duree']); ?></small>
                            <a href="<?php echo h($baseUrl . '/controllers/offer_emploi/offer_details.php?id_offer=' . $offer['id_offer']); ?>" class="btn btn-sm btn-success rounded-pill">Postuler <i class="fas fa-paper-plane ms-1"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- ARTISANS SECTION -->
    <?php if (count($artisans) > 0): ?>
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4 mt-5 section-header">
        <div>
            <h2 class="section-title">Artisans à découvrir</h2>
            <p class="section-subtitle">Rencontrez les talents de notre communauté</p>
        </div>
        <a href="<?php echo h($baseUrl . '/controllers/user/artisans.php'); ?>" class="btn btn-outline-success rounded-pill"><i class="fas fa-arrow-right me-2"></i>Voir tous</a>
    </div>
    
    <div class="row g-4 mb-5">
        <?php foreach ($artisans as $art): ?>
            <div class="col-md-6 col-lg-3">
                <div class="artisan-card text-center reveal-on-scroll">
                    <div class="card-img-top d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, var(--primary-brown), #C49A6C);">
                        <?php if (!empty($art['avatar'])): ?>
                            <img src="<?php echo h($art['avatar']); ?>" alt="Avatar de <?php echo h($art['prenom']); ?>" style="width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid white;" loading="lazy">
                        <?php else: ?>
                            <i class="fas fa-user-circle fa-4x text-white"></i>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="card-title"><?php echo h($art['prenom'] . ' ' . $art['nom']); ?></div>
                        <div class="badge-custom mb-2">
                            <i class="fas fa-tools"></i> <?php echo h($art['specialite'] ?? 'Artisan'); ?>
                        </div>
                        <?php if (!empty($art['ville'])): ?>
                            <div class="small text-muted mb-2"><i class="fas fa-map-marker-alt"></i> <?php echo h($art['ville']); ?></div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star text-warning"></i>
                            <?php endfor; ?>
                        </div>
                        <a href="<?php echo h($baseUrl . '/controllers/user/profile.php?user_id=' . $art['id_user']); ?>" class="btn btn-sm btn-outline-success rounded-pill">Voir le profil</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- PERSONALIZED USER SECTION - Artisan Applications -->
    <?php if ($userRole === 'artisan' && count($userApplications) > 0): ?>
    <div class="user-section reveal-on-scroll">
        <div class="section-header-custom">
            <h4 class="mb-0"><i class="fas fa-file-alt text-success me-2"></i>Mes candidatures récentes</h4>
        </div>
        <div class="row">
            <?php foreach ($userApplications as $app): ?>
                <div class="col-md-6 mb-3">
                    <div class="border rounded-3 p-3 h-100 bg-white">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <strong><?php echo h($app['offre_titre']); ?></strong>
                            <span class="application-status status-<?php echo $app['status']; ?>">
                                <i class="fas <?php echo $app['status'] === 'pending' ? 'fa-clock' : ($app['status'] === 'accepted' ? 'fa-check-circle' : 'fa-times-circle'); ?>"></i>
                                <?php echo ucfirst($app['status']); ?>
                            </span>
                        </div>
                        <div class="small text-muted mb-2">
                            <i class="fas fa-building"></i> <?php echo h($app['entreprise_name'] ?? $app['prenom'] . ' ' . $app['nom']); ?>
                        </div>
                        <div class="small text-muted">
                            <i class="far fa-calendar-alt"></i> Postulée le <?php echo date('d/m/Y', strtotime($app['date_creation'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-3">
            <a href="<?php echo h($baseUrl . '/controllers/offer_emploi/my_applications.php'); ?>" class="btn btn-sm btn-outline-success rounded-pill">Voir toutes mes candidatures <i class="fas fa-arrow-right ms-1"></i></a>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- PERSONALIZED USER SECTION - Recruiter Offers -->
    <?php if (($userRole === 'recruteur' || $userRole === 'entrepreneur') && count($userOffers) > 0): ?>
    <div class="user-section reveal-on-scroll">
        <div class="section-header-custom">
            <h4 class="mb-0"><i class="fas fa-chart-line text-success me-2"></i>Mes offres d'emploi</h4>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Titre</th>
                        <th>Budget</th>
                        <th>Candidatures</th>
                        <th>En attente</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userOffers as $offer): ?>
                    <tr>
                        <td><strong><?php echo h($offer['titre']); ?></strong></td>
                        <td><?php echo formatCurrency($offer['budget']); ?></td>
                        <td><span class="badge bg-info"><?php echo (int)$offer['app_count']; ?></span></td>
                        <td><span class="badge bg-warning text-dark"><?php echo (int)($offer['pending_count'] ?? 0); ?></span></td>
                        <td><span class="badge bg-<?php echo getStatusBadgeClass($offer['status']); ?>"><?php echo ucfirst($offer['status']); ?></span></td>
                        <td><a href="<?php echo h($baseUrl . '/controllers/offer_emploi/recruiter_dashboard.php?offer=' . $offer['id_offer']); ?>" class="btn btn-sm btn-outline-success rounded-pill">Gérer</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="text-center mt-3">
            <a href="<?php echo h($baseUrl . '/controllers/offer_emploi/create_offre.php'); ?>" class="btn btn-success rounded-pill"><i class="fas fa-plus me-2"></i>Créer une nouvelle offre</a>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- PERSONALIZED USER SECTION - Recruiter Projects -->
    <?php if (($userRole === 'recruteur' || $userRole === 'entrepreneur') && count($userProjects) > 0): ?>
    <div class="user-section reveal-on-scroll">
        <div class="section-header-custom">
            <h4 class="mb-0"><i class="fas fa-project-diagram text-success me-2"></i>Mes projets</h4>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Titre</th>
                        <th>Budget min</th>
                        <th>Budget max</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userProjects as $proj): ?>
                    <tr>
                        <td><strong><?php echo h($proj['titre']); ?></strong></td>
                        <td><?php echo formatCurrency($proj['budget_min']); ?></td>
                        <td><?php echo $proj['budget_max'] ? formatCurrency($proj['budget_max']) : '-'; ?></td>
                        <td><span class="badge bg-<?php echo getStatusBadgeClass($proj['status']); ?>"><?php echo ucfirst($proj['status']); ?></span></td>
                        <td><small><?php echo date('d/m/Y', strtotime($proj['date_creation'])); ?></small></td>
                        <td><a href="<?php echo h($baseUrl . '/controllers/projects/projet_details.php?id=' . $proj['id']); ?>" class="btn btn-sm btn-outline-success rounded-pill">Voir</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- CTA SECTION -->
    <div class="cta-section reveal-on-scroll">
        <div class="container">
            <h2>Rejoignez notre communauté</h2>
            <p class="mb-4">Faites briller votre savoir-faire et connectez-vous avec les meilleurs talents et investisseurs tunisiens</p>
            <div>
                <a href="<?php echo h($baseUrl . '/controllers/user/profile.php'); ?>" class="btn-light-cta me-3"><i class="fas fa-user-plus me-2"></i>Compléter mon profil</a>
                <a href="<?php echo h($baseUrl . '/controllers/projects/projets.php'); ?>" class="btn-light-cta btn-outline-light-cta"><i class="fas fa-rocket me-2"></i>Explorer les projets</a>
            </div>
        </div>
    </div>
</div>

<!-- FOOTER -->
<footer class="footer-section">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4 mb-md-0">
                <img src="<?php echo h($baseUrl . '/public/assets/img/logo_herfa.png'); ?>" alt="Logo حرفة" height="50" class="mb-3">
                <p class="small">حرفة Tunisie - La plateforme dédiée à l'artisanat tunisien et à l'entrepreneuriat responsable.</p>
                <div class="mt-3">
                    <a href="#" class="me-3" aria-label="Facebook"><i class="fab fa-facebook fa-lg"></i></a>
                    <a href="#" class="me-3" aria-label="Instagram"><i class="fab fa-instagram fa-lg"></i></a>
                    <a href="#" class="me-3" aria-label="LinkedIn"><i class="fab fa-linkedin fa-lg"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fab fa-youtube fa-lg"></i></a>
                </div>
            </div>
            <div class="col-md-2 mb-4 mb-md-0">
                <h6 class="mb-3">Liens rapides</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="<?php echo h($baseUrl . '/controllers/home.php'); ?>">Accueil</a></li>
                    <li class="mb-2"><a href="<?php echo h($baseUrl . '/controllers/projects/projets.php'); ?>">Projets</a></li>
                    <li class="mb-2"><a href="<?php echo h($baseUrl . '/controllers/formation/formations.php'); ?>">Formations</a></li>
                    <li class="mb-2"><a href="<?php echo h($baseUrl . '/controllers/offer_emploi/offres.php'); ?>">Emplois</a></li>
                </ul>
            </div>
            <div class="col-md-3 mb-4 mb-md-0">
                <h6 class="mb-3">Ressources</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="<?php echo h($baseUrl . '/blog.php'); ?>">Blog</a></li>
                    <li class="mb-2"><a href="<?php echo h($baseUrl . '/faq.php'); ?>">FAQ</a></li>
                    <li class="mb-2"><a href="<?php echo h($baseUrl . '/support.php'); ?>">Support</a></li>
                    <li class="mb-2"><a href="<?php echo h($baseUrl . '/legal.php'); ?>">Mentions légales</a></li>
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
            <p class="mb-0">&copy; <?php echo date('Y'); ?> حرفة Tunisie - Tous droits réservés</p>
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Enhanced JavaScript with performance optimizations
    (function() {
        'use strict';
        
        // Check for reduced motion preference
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        
        // DOM Elements
        const progress = document.getElementById('scrollProgress');
        const revealElements = document.querySelectorAll('.stat-card, .project-card, .formation-card, .offer-card, .artisan-card, .user-section, .cta-section, .quick-actions-shell, .action-tile, .snapshot-chip');
        const cards = document.querySelectorAll('.project-card, .formation-card, .offer-card, .artisan-card');
        const counters = document.querySelectorAll('[data-counter-target]');
        const navbar = document.getElementById('mainNavbar');
        
        // Add reveal class to elements
        revealElements.forEach((el, index) => {
            el.classList.add('reveal-on-scroll');
            if (!reduceMotion) {
                el.style.transitionDelay = `${Math.min(index * 45, 260)}ms`;
            }
        });
        
        // Scroll progress bar
        function updateScrollProgress() {
            if (!progress) return;
            const scrollTop = window.scrollY;
            const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
            const ratio = maxScroll > 0 ? Math.min(scrollTop / maxScroll, 1) : 0;
            progress.style.width = `${ratio * 100}%`;
        }
        
        // Navbar scroll effect
        function updateNavbar() {
            if (!navbar) return;
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        }
        
        updateScrollProgress();
        updateNavbar();
        window.addEventListener('scroll', () => {
            updateScrollProgress();
            updateNavbar();
        }, { passive: true });
        
        // Intersection Observer for reveal animations
        if (!reduceMotion && 'IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries, obs) => {
                entries.forEach(entry => {
                    if (!entry.isIntersecting) return;
                    
                    entry.target.classList.add('is-visible');
                    
                    // Handle counter animations
                    if (entry.target.hasAttribute('data-counter-target')) {
                        const endValue = parseInt(entry.target.getAttribute('data-counter-target') || '0', 10);
                        const duration = 1000;
                        const startAt = performance.now();
                        
                        const tick = (now) => {
                            const elapsed = now - startAt;
                            const progressRatio = Math.min(elapsed / duration, 1);
                            const eased = 1 - Math.pow(1 - progressRatio, 3);
                            entry.target.textContent = String(Math.floor(endValue * eased));
                            if (progressRatio < 1) {
                                requestAnimationFrame(tick);
                            } else {
                                entry.target.textContent = String(endValue);
                            }
                        };
                        
                        requestAnimationFrame(tick);
                    }
                    
                    obs.unobserve(entry.target);
                });
            }, { threshold: 0.14, rootMargin: '0px 0px -50px 0px' });
            
            revealElements.forEach(el => observer.observe(el));
            counters.forEach(counter => observer.observe(counter));
        } else {
            // Fallback for no observer or reduced motion
            revealElements.forEach(el => el.classList.add('is-visible'));
            counters.forEach(counter => {
                counter.textContent = counter.getAttribute('data-counter-target') || '0';
            });
        }
        
        // 3D card tilt effect (only if motion not reduced)
        if (!reduceMotion) {
            cards.forEach(card => {
                card.addEventListener('mousemove', (event) => {
                    const rect = card.getBoundingClientRect();
                    const x = (event.clientX - rect.left) / rect.width;
                    const y = (event.clientY - rect.top) / rect.height;
                    const rotateY = (x - 0.5) * 8;
                    const rotateX = (0.5 - y) * 6;
                    card.style.transform = `perspective(900px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-8px)`;
                });
                
                card.addEventListener('mouseleave', () => {
                    card.style.transform = '';
                });
            });
        }
        
        // Hero canvas animation
        if (!reduceMotion) {
            const hero = document.querySelector('.hero-banner');
            const canvas = document.getElementById('heroCanvas');
            const glow = document.getElementById('heroGlow');
            
            if (hero && canvas) {
                const ctx = canvas.getContext('2d');
                if (ctx) {
                    let width = 0, height = 0;
                    const particles = [];
                    const particleCount = window.innerWidth < 768 ? 20 : 34;
                    
                    function resizeCanvas() {
                        const rect = hero.getBoundingClientRect();
                        width = Math.max(1, Math.floor(rect.width));
                        height = Math.max(1, Math.floor(rect.height));
                        canvas.width = width;
                        canvas.height = height;
                        createParticles();
                    }
                    
                    function createParticles() {
                        particles.length = 0;
                        for (let i = 0; i < particleCount; i++) {
                            particles.push({
                                x: Math.random() * width,
                                y: Math.random() * height,
                                vx: (Math.random() - 0.5) * 0.35,
                                vy: (Math.random() - 0.5) * 0.35,
                                r: Math.random() * 2 + 1,
                                alpha: Math.random() * 0.5 + 0.18
                            });
                        }
                    }
                    
                    function animateCanvas() {
                        if (!ctx) return;
                        ctx.clearRect(0, 0, width, height);
                        
                        for (let i = 0; i < particles.length; i++) {
                            const p = particles[i];
                            p.x += p.vx;
                            p.y += p.vy;
                            
                            if (p.x < 0 || p.x > width) p.vx *= -1;
                            if (p.y < 0 || p.y > height) p.vy *= -1;
                            
                            ctx.beginPath();
                            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                            ctx.fillStyle = `rgba(245, 236, 215, ${p.alpha})`;
                            ctx.fill();
                            
                            for (let j = i + 1; j < particles.length; j++) {
                                const q = particles[j];
                                const dx = p.x - q.x;
                                const dy = p.y - q.y;
                                const dist = Math.sqrt(dx * dx + dy * dy);
                                if (dist < 140) {
                                    ctx.beginPath();
                                    ctx.moveTo(p.x, p.y);
                                    ctx.lineTo(q.x, q.y);
                                    ctx.strokeStyle = `rgba(245, 236, 215, ${(1 - dist / 140) * 0.16})`;
                                    ctx.lineWidth = 1;
                                    ctx.stroke();
                                }
                            }
                        }
                        
                        requestAnimationFrame(animateCanvas);
                    }
                    
                    resizeCanvas();
                    animateCanvas();
                    
                    window.addEventListener('resize', () => {
                        resizeCanvas();
                    });
                    
                    // Mouse move effect for glow
                    if (glow) {
                        hero.addEventListener('mousemove', (event) => {
                            const rect = hero.getBoundingClientRect();
                            const x = (event.clientX - rect.left) / rect.width;
                            const y = (event.clientY - rect.top) / rect.height;
                            const moveX = (x - 0.5) * 36;
                            const moveY = (y - 0.5) * 24;
                            glow.style.transform = `translate(-50%, 0) translate(${moveX}px, ${moveY}px)`;
                        });
                    }
                }
            }
        }
        
        // Toast notification function
        window.showToast = function(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            
            const colors = {
                success: '#28a745',
                error: '#dc3545',
                warning: '#ffc107',
                info: '#17a2b8'
            };
            
            const toast = document.createElement('div');
            toast.className = 'toast align-items-center text-white border-0 show';
            toast.setAttribute('role', 'alert');
            toast.style.background = colors[type] || colors.success;
            toast.style.borderRadius = '8px';
            toast.style.marginTop = '10px';
            toast.style.minWidth = '280px';
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${escapeHtml(message)}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        };
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Check for URL parameters (success/error messages)
        const urlParams = new URLSearchParams(window.location.search);
        const successMsg = urlParams.get('success');
        const errorMsg = urlParams.get('error');
        
        if (successMsg) {
            showToast(decodeURIComponent(successMsg), 'success');
            // Clean URL without reload
            const newUrl = window.location.pathname;
            window.history.replaceState({}, document.title, newUrl);
        }
        
        if (errorMsg) {
            showToast(decodeURIComponent(errorMsg), 'error');
            const newUrl = window.location.pathname;
            window.history.replaceState({}, document.title, newUrl);
        }
    })();
</script>
</body>
</html>
