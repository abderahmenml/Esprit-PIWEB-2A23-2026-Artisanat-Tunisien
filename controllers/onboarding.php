<?php
declare(strict_types=1);

// Require files relative to the `herfa` workspace root (one level up)
require_once dirname(__DIR__, 1) . '/config/Config.php';
require_once dirname(__DIR__, 1) . '/views/partials/app_header.php';
require_auth();

$userId = (int)($_SESSION['user_id'] ?? 0);
// Normalize session role to lowercase to handle existing sessions
$userRole = mb_strtolower((string)($_SESSION['role'] ?? ''), 'UTF-8');
$baseUrl = app_base_url();

if ($userRole !== 'artisan') {
    app_redirect('controllers/home.php');
}

// --- CSRF Protection ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// --- Database Schema Management (optimized with flag to avoid repeated checks) ---
$schemaReadyKey = "schema_ready_artisan";
if (!isset($_SESSION[$schemaReadyKey]) || !$_SESSION[$schemaReadyKey]) {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS artisan_onboarding (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            skills text NOT NULL,
            interests text NOT NULL,
            work_types text DEFAULT NULL,
            craft_focus text DEFAULT NULL,
            project_interests text DEFAULT NULL,
            job_interests text DEFAULT NULL,
            formation_interests text DEFAULT NULL,
            tools_materials text DEFAULT NULL,
            experience_level varchar(50) DEFAULT NULL,
            experience_years varchar(50) DEFAULT NULL,
            collaboration_styles text DEFAULT NULL,
            availability text DEFAULT NULL,
            location_preferences text DEFAULT NULL,
            market_channels text DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT current_timestamp(),
            updated_at datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (id),
            UNIQUE KEY uq_artisan_onboarding_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    $columnDefinitions = [
        'craft_focus' => 'text DEFAULT NULL',
        'project_interests' => 'text DEFAULT NULL',
        'job_interests' => 'text DEFAULT NULL',
        'formation_interests' => 'text DEFAULT NULL',
        'tools_materials' => 'text DEFAULT NULL',
        'experience_level' => 'varchar(50) DEFAULT NULL',
        'experience_years' => 'varchar(50) DEFAULT NULL',
        'collaboration_styles' => 'text DEFAULT NULL',
        'availability' => 'text DEFAULT NULL',
        'location_preferences' => 'text DEFAULT NULL',
        'market_channels' => 'text DEFAULT NULL'
    ];

    $columnCheck = $pdo->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );

    foreach ($columnDefinitions as $name => $definition) {
        $columnCheck->execute(['artisan_onboarding', $name]);
        if ((int)$columnCheck->fetchColumn() === 0) {
            $pdo->exec(sprintf('ALTER TABLE artisan_onboarding ADD COLUMN %s %s', $name, $definition));
        }
    }

    $idExtraStmt = $pdo->prepare(
        'SELECT EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $idExtraStmt->execute(['artisan_onboarding', 'id']);
    $idExtra = (string)$idExtraStmt->fetchColumn();
    if ($idExtra !== '' && stripos($idExtra, 'auto_increment') === false) {
        $pdo->exec('ALTER TABLE artisan_onboarding MODIFY id int(11) NOT NULL AUTO_INCREMENT');
    }

    $uniqueCheck = $pdo->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
    );
    $uniqueCheck->execute(['artisan_onboarding', 'uq_artisan_onboarding_user']);
    if ((int)$uniqueCheck->fetchColumn() === 0) {
        $pdo->exec('ALTER TABLE artisan_onboarding ADD UNIQUE KEY uq_artisan_onboarding_user (user_id)');
    }
    $_SESSION[$schemaReadyKey] = true;
}

// --- Fetch existing data ---
$existingStmt = $pdo->prepare('SELECT * FROM artisan_onboarding WHERE user_id = ? LIMIT 1');
$existingStmt->execute([$userId]);
$existing = $existingStmt->fetch() ?: null;

if ($existing && !empty($existing['completed_at'])) {
    app_redirect('controllers/home.php');
}

// --- Helper functions ---
function normalize_list($value): array {
    if (is_array($value)) {
        return array_values(array_filter(array_map('trim', $value)));
    }
    return array_values(array_filter(array_map('trim', preg_split('/[,;\n]+/', (string)$value) ?: [])));
}

function h(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// --- Form processing ---
$errors = [];
$success = false;
$isDraftSaved = false;
$redirectUrl = $baseUrl . 'controllers/home.php';

// Pre-fill from existing data
$craftFocus = normalize_list($existing['craft_focus'] ?? ($existing['skills'] ?? ''));
$projectInterests = normalize_list($existing['project_interests'] ?? '');
$jobInterests = normalize_list($existing['job_interests'] ?? '');
$formationInterests = normalize_list($existing['formation_interests'] ?? '');
$toolsMaterials = normalize_list($existing['tools_materials'] ?? '');
$collaborationStyles = normalize_list($existing['collaboration_styles'] ?? '');
$availability = normalize_list($existing['availability'] ?? '');
$locationPreferences = normalize_list($existing['location_preferences'] ?? '');
$marketChannels = normalize_list($existing['market_channels'] ?? '');
$workTypes = normalize_list($existing['work_types'] ?? []);
$experienceLevel = trim((string)($existing['experience_level'] ?? ''));
$experienceYears = trim((string)($existing['experience_years'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = 'Erreur de sécurité. Veuillez réessayer.';
    } else {
        $action = (string)($_POST['onboard_action'] ?? 'complete');
        $isSkip = $action === 'skip';
        $isDraft = $action === 'draft';

        // Get POST data
        $craftFocus = normalize_list($_POST['craft_focus'] ?? []);
        $projectInterests = normalize_list($_POST['project_interests'] ?? []);
        $jobInterests = normalize_list($_POST['job_interests'] ?? []);
        $formationInterests = normalize_list($_POST['formation_interests'] ?? []);
        $toolsMaterials = normalize_list($_POST['tools_materials'] ?? []);
        $collaborationStyles = normalize_list($_POST['collaboration_styles'] ?? []);
        $availability = normalize_list($_POST['availability'] ?? []);
        $locationPreferences = normalize_list($_POST['location_preferences'] ?? []);
        $marketChannels = normalize_list($_POST['market_channels'] ?? []);
        $workTypes = normalize_list($_POST['work_types'] ?? []);
        $experienceLevel = trim((string)($_POST['experience_level'] ?? ''));
        $experienceYears = trim((string)($_POST['experience_years'] ?? ''));

        // Validation (skip bypasses required checks)
        if (!$isSkip && !$isDraft) {
            if (empty($craftFocus)) {
                $errors[] = 'Sélectionnez au moins un domaine artisanal.';
            }
            $hasInterest = !empty($projectInterests) || !empty($jobInterests) || !empty($formationInterests);
            if (!$hasInterest) {
                $errors[] = 'Choisissez au moins un sujet parmi les projets, emplois ou formations.';
            }
            if ($experienceLevel === '') {
                $errors[] = "Indiquez votre niveau d'expérience.";
            }
        }

        if (empty($errors)) {
            $completedAt = null;
            if (!$isSkip && !$isDraft) {
                $completedAt = date('Y-m-d H:i:s');
            }
            
            $craftFocusText = implode(', ', $craftFocus);
            $projectInterestsText = !empty($projectInterests) ? implode(', ', $projectInterests) : null;
            $jobInterestsText = !empty($jobInterests) ? implode(', ', $jobInterests) : null;
            $formationInterestsText = !empty($formationInterests) ? implode(', ', $formationInterests) : null;
            $toolsMaterialsText = !empty($toolsMaterials) ? implode(', ', $toolsMaterials) : null;
            $collaborationStylesText = !empty($collaborationStyles) ? implode(', ', $collaborationStyles) : null;
            $availabilityText = !empty($availability) ? implode(', ', $availability) : null;
            $locationPreferencesText = !empty($locationPreferences) ? implode(', ', $locationPreferences) : null;
            $marketChannelsText = !empty($marketChannels) ? implode(', ', $marketChannels) : null;
            $workTypesText = !empty($workTypes) ? implode(', ', $workTypes) : null;
            $interestsText = implode(', ', array_filter(array_merge($projectInterests, $jobInterests, $formationInterests)));

            if ($existing) {
                $stmt = $pdo->prepare(
                    'UPDATE artisan_onboarding SET skills = ?, interests = ?, work_types = ?, craft_focus = ?, project_interests = ?, job_interests = ?, formation_interests = ?, tools_materials = ?, experience_level = ?, experience_years = ?, collaboration_styles = ?, availability = ?, location_preferences = ?, market_channels = ?, completed_at = ? WHERE user_id = ?'
                );
                $stmt->execute([
                    $craftFocusText, $interestsText, $workTypesText, $craftFocusText,
                    $projectInterestsText, $jobInterestsText, $formationInterestsText,
                    $toolsMaterialsText, $experienceLevel, $experienceYears,
                    $collaborationStylesText, $availabilityText, $locationPreferencesText,
                    $marketChannelsText, $completedAt, $userId
                ]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO artisan_onboarding (user_id, skills, interests, work_types, craft_focus, project_interests, job_interests, formation_interests, tools_materials, experience_level, experience_years, collaboration_styles, availability, location_preferences, market_channels, completed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $userId, $craftFocusText, $interestsText, $workTypesText, $craftFocusText,
                    $projectInterestsText, $jobInterestsText, $formationInterestsText,
                    $toolsMaterialsText, $experienceLevel, $experienceYears,
                    $collaborationStylesText, $availabilityText, $locationPreferencesText,
                    $marketChannelsText, $completedAt
                ]);
            }

            if ($isSkip) {
                unset($_SESSION['onboarding_complete']);
                $_SESSION['onboarding_skip_until_logout'] = true;
                $success = true;
            } elseif ($isDraft) {
                $isDraftSaved = true;
                // Refresh existing data after draft save
                $existingStmt->execute([$userId]);
                $existing = $existingStmt->fetch() ?: null;
                // Keep sessions clean - user still needs to complete later
                unset($_SESSION['onboarding_complete']);
            } else {
                $_SESSION['onboarding_complete'] = true;
                unset($_SESSION['onboarding_skip_until_logout']);
                $success = true;
            }
        }
    }
}

// --- Data arrays for options ---
$craftFocusOptions = [
    'Poterie et céramique', 'Broderie et couture', 'Bois et marqueterie',
    'Métal et ferronnerie', 'Cuir et maroquinerie', 'Textile et tissage',
    'Bijouterie', 'Verre et vitrail', 'Peinture et illustration',
    'Papier et calligraphie', 'Design produit', 'Art digital'
];
$projectInterestOptions = [
    'Commandes personnalisées', 'Restauration', 'Séries limitées', 'Prototypes',
    'Événements', 'Collaborations artistiques', 'Design intérieur', 'Projets éco-responsables'
];
$jobInterestOptions = [
    'Atelier artisanal', 'Entreprise locale', 'Marque internationale', 'Coopérative',
    'Résidence artistique', 'Enseignement', 'Mentorat', 'Gestion atelier'
];
$formationInterestOptions = [
    'Marketing et vente', 'Pricing et rentabilité', 'E-commerce', 'Photographie produit',
    'Branding', 'Gestion de stock', 'Export', 'Qualité et certification'
];
$toolsMaterialsOptions = [
    'Bois', 'Argile', 'Métal', 'Cuir', 'Textile', 'Verre', 'Pierre', 'Papier', 'Peinture', 'Recyclage', 'Numérique'
];
$collaborationOptions = [
    'Solo', 'En équipe', 'Avec designers', 'Avec marques', 'Avec architectes', 'Avec communautés'
];
$availabilityOptions = [
    'Semaine', 'Soirs', 'Week-ends', 'Saisonnier', 'Flexible'
];
$locationOptions = [
    'Local', 'Régional', 'National', 'International', 'À distance'
];
$marketOptions = [
    'Marchés locaux', 'Boutique en ligne', 'Réseaux sociaux', 'Boutiques partenaires', 'Export'
];
$experienceLevels = [
    'Débutant', 'Intermédiaire', 'Avancé', 'Expert'
];
$experienceYearsOptions = [
    '0-1 an', '2-4 ans', '5-9 ans', '10+ ans'
];
$workTypeOptions = [
    'Freelance', 'Temps plein', 'Temps partiel', 'Mission courte', 'À distance', 'Sur site', 'Apprentissage', 'Saisonnier'
];

// Step labels for progress bar
$stepLabels = [
    'Domaines', 'Projets', 'Emplois', 'Formations', 'Expérience', 'Années pratique',
    'Matériaux', 'Collaboration', 'Disponibilité', 'Zone', 'Vente', 'Types travail', 'Récap'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <title>Onboarding Artisan | CraftLink Tunisie</title>
    <link rel="icon" type="image/x-icon" href="<?php echo h($baseUrl . 'public/assets/favicon.ico'); ?>" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        <?php echo app_header_styles(); ?>
        :root {
            --craft-brown: #3B2314;
            --craft-green: #2E6B3E;
            --craft-cream: #F5ECD7;
            --light-bg: #fefaf5;
            --transition-default: all 0.25s cubic-bezier(0.2, 0.9, 0.4, 1.1);
        }
        body {
            background: linear-gradient(145deg, #f2ede5 0%, #e6ddd0 100%);
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
        }
        .onboard-card {
            background: rgba(255, 255, 255, 0.97);
            border-radius: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            padding: 2rem 2rem 2.5rem;
            transition: var(--transition-default);
        }
        .onboard-header {
            background: linear-gradient(125deg, var(--craft-brown), var(--craft-green));
            color: var(--craft-cream);
            border-radius: 1.5rem;
            padding: 1.8rem;
            margin-bottom: 2rem;
        }
        /* Enhanced step progress */
        .step-progress {
            margin-bottom: 2rem;
        }
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            flex-wrap: wrap;
            gap: 0.25rem;
        }
        .step-item {
            flex: 1;
            text-align: center;
            font-size: 0.7rem;
            font-weight: 600;
            color: #adb5bd;
            position: relative;
            cursor: pointer;
            transition: var(--transition-default);
            padding: 0.5rem 0;
            border-radius: 2rem;
        }
        .step-item.active {
            color: var(--craft-green);
            background: rgba(46, 107, 62, 0.1);
        }
        .step-item.completed {
            color: var(--craft-green);
        }
        .step-item .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #e9ecef;
            margin-right: 0.3rem;
            font-size: 0.75rem;
            font-weight: bold;
        }
        .step-item.active .step-number,
        .step-item.completed .step-number {
            background: var(--craft-green);
            color: white;
        }
        .step-item.completed .step-number::after {
            content: "✓";
            font-size: 0.75rem;
        }
        .step-item.completed .step-number span {
            display: none;
        }
        .progress-bar-custom {
            height: 6px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin: 0.5rem 0 1rem;
        }
        .progress-fill {
            width: 0%;
            height: 100%;
            background: var(--craft-green);
            transition: width 0.3s ease;
        }
        .question-panel {
            display: none;
            animation: fadeSlideIn 0.4s cubic-bezier(0.2, 0.9, 0.4, 1.1);
        }
        .question-panel.is-active {
            display: block;
        }
        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .choice-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 0.8rem;
            margin: 1.5rem 0;
        }
        .choice-card, .rating-card {
            background: white;
            border-radius: 1rem;
            padding: 0.8rem 1rem;
            border: 1px solid rgba(46, 107, 62, 0.2);
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .choice-card:hover, .rating-card:hover {
            transform: translateY(-2px);
            border-color: var(--craft-green);
            box-shadow: 0 6px 14px rgba(0,0,0,0.08);
        }
        .choice-card.selected, .rating-card.selected {
            background: rgba(46, 107, 62, 0.08);
            border-color: var(--craft-green);
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        .choice-card input, .rating-card input {
            position: absolute;
            opacity: 0;
        }
        .choice-card::before {
            font-family: "Font Awesome 6 Free";
            content: "\f0c8";
            font-weight: 400;
            color: #aaa;
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }
        .choice-card.selected::before {
            content: "\f14a";
            font-weight: 900;
            color: var(--craft-green);
        }
        .rating-card {
            justify-content: center;
            font-weight: 600;
        }
        .rating-card.selected {
            background: var(--craft-green);
            color: white;
            border-color: var(--craft-green);
        }
        .badge-required {
            background: #ffebee;
            color: #b00020;
            border-radius: 30px;
            padding: 0.2rem 0.8rem;
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: 1rem;
        }
        .badge-optional {
            background: #e9ecef;
            color: #495057;
            border-radius: 30px;
            padding: 0.2rem 0.8rem;
            font-size: 0.7rem;
            margin-left: 1rem;
        }
        .step-error {
            background: #fff0f0;
            border-radius: 1rem;
            padding: 0.5rem 1rem;
            color: #c00;
            margin: 0.5rem 0;
            font-size: 0.85rem;
        }
        .review-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            margin: 1rem 0;
            max-height: 300px;
            overflow-y: auto;
        }
        .summary-pill {
            background: #f1ede6;
            padding: 0.4rem 1rem;
            border-radius: 40px;
            font-size: 0.85rem;
            transition: var(--transition-default);
        }
        .btn-custom {
            border-radius: 40px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: var(--transition-default);
        }
        .btn-success {
            background: var(--craft-green);
            border: none;
        }
        .btn-success:hover {
            background: #1f4a2c;
            transform: translateY(-1px);
        }
        h2 {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--craft-brown);
        }
        .required-star {
            color: #dc3545;
        }
        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1100;
            min-width: 250px;
        }
        @media (max-width: 768px) {
            .onboard-card { padding: 1.5rem; }
            .choice-grid { grid-template-columns: 1fr; }
            .step-item .step-label { display: none; }
            .step-item .step-number { margin-right: 0; }
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            backdrop-filter: blur(2px);
        }
        .spinner-border-custom {
            width: 3rem;
            height: 3rem;
        }
    </style>
</head>
<body>
<?php render_app_header('home'); ?>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="onboard-header">
                <h1 class="h3 mb-2"><i class="fa-regular fa-compass me-2"></i> Façonner votre profil artisanal</h1>
                <p class="mb-0">Répondez étape par étape. Les champs <span class="required-star">*</span> sont obligatoires pour finaliser. Vous pouvez enregistrer un brouillon à tout moment.</p>
            </div>
            <div class="onboard-card">
                <?php if ($errors): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong><i class="fa-solid fa-circle-exclamation"></i> Correction requise</strong>
                        <?php foreach ($errors as $err): echo "<div>• ".h($err)."</div>"; endforeach; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if ($isDraftSaved): ?>
                    <div class="alert alert-info alert-dismissible fade show">
                        <i class="fa-regular fa-floppy-disk"></i> Brouillon enregistré ! Vous pourrez continuer plus tard.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="text-center py-5">
                        <i class="fa-regular fa-circle-check fa-4x" style="color: var(--craft-green);"></i>
                        <h2 class="h4 mt-3">Profil enregistré !</h2>
                        <p>Redirection vers votre espace personnel...</p>
                    </div>
                <?php else: ?>
                <form method="POST" id="onboardingForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                    
                    <!-- Enhanced progress indicator -->
                    <div class="step-progress">
                        <div class="progress-steps" id="stepLabelsContainer"></div>
                        <div class="progress-bar-custom">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                    </div>

                    <!-- Steps container -->
                    <div id="stepsContainer">
                        <!-- 1. Craft focus (required) -->
                        <div class="question-panel" data-required="true" data-group="craft_focus" data-msg="Choisissez au moins un domaine artisanal">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <h2>Vos domaines artisanaux <span class="required-star">*</span></h2>
                                <span class="badge-required">Obligatoire</span>
                            </div>
                            <p>Quels sont vos savoir-faire principaux ? (plusieurs choix possibles)</p>
                            <div class="choice-grid">
                                <?php foreach ($craftFocusOptions as $opt): $checked = in_array($opt, $craftFocus, true); ?>
                                <label class="choice-card <?php echo $checked ? 'selected' : ''; ?>">
                                    <input type="checkbox" name="craft_focus[]" value="<?php echo h($opt); ?>" <?php echo $checked ? 'checked' : ''; ?> />
                                    <?php echo h($opt); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="step-error"></div>
                        </div>

                        <!-- 2. Project interests -->
                        <div class="question-panel" data-group="project_interests" data-part-of-group="interest_group">
                            <div class="d-flex justify-content-between flex-wrap">
                                <h2>Projets qui vous attirent</h2>
                                <span class="badge-optional">Optionnel</span>
                            </div>
                            <p>Sélectionnez les types de projets qui vous inspirent.</p>
                            <div class="choice-grid">
                                <?php foreach ($projectInterestOptions as $opt): $checked = in_array($opt, $projectInterests, true); ?>
                                <label class="choice-card <?php echo $checked ? 'selected' : ''; ?>">
                                    <input type="checkbox" name="project_interests[]" value="<?php echo h($opt); ?>" <?php echo $checked ? 'checked' : ''; ?> />
                                    <?php echo h($opt); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- 3. Job interests -->
                        <div class="question-panel" data-group="job_interests" data-part-of-group="interest_group">
                            <div class="d-flex justify-content-between flex-wrap">
                                <h2>Types de missions ou emplois</h2>
                                <span class="badge-optional">Optionnel</span>
                            </div>
                            <div class="choice-grid">
                                <?php foreach ($jobInterestOptions as $opt): $checked = in_array($opt, $jobInterests, true); ?>
                                <label class="choice-card <?php echo $checked ? 'selected' : ''; ?>">
                                    <input type="checkbox" name="job_interests[]" value="<?php echo h($opt); ?>" <?php echo $checked ? 'checked' : ''; ?> />
                                    <?php echo h($opt); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- 4. Formation interests -->
                        <div class="question-panel" data-group="formation_interests" data-part-of-group="interest_group" data-group-exit-check="true">
                            <div class="d-flex justify-content-between flex-wrap">
                                <h2>Formations souhaitées</h2>
                                <span class="badge-optional">Optionnel</span>
                            </div>
                            <div class="choice-grid">
                                <?php foreach ($formationInterestOptions as $opt): $checked = in_array($opt, $formationInterests, true); ?>
                                <label class="choice-card <?php echo $checked ? 'selected' : ''; ?>">
                                    <input type="checkbox" name="formation_interests[]" value="<?php echo h($opt); ?>" <?php echo $checked ? 'checked' : ''; ?> />
                                    <?php echo h($opt); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="step-error"></div>
                        </div>

                        <!-- 5. Experience level (required) -->
                        <div class="question-panel" data-required="true" data-group="experience_level" data-msg="Choisissez votre niveau d'expérience">
                            <div class="d-flex justify-content-between flex-wrap">
                                <h2>Niveau d'expérience <span class="required-star">*</span></h2>
                                <span class="badge-required">Obligatoire</span>
                            </div>
                            <div class="choice-grid">
                                <?php foreach ($experienceLevels as $opt): $checked = ($experienceLevel === $opt); ?>
                                <label class="rating-card <?php echo $checked ? 'selected' : ''; ?>">
                                    <input type="radio" name="experience_level" value="<?php echo h($opt); ?>" <?php echo $checked ? 'checked' : ''; ?> />
                                    <?php echo h($opt); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="step-error"></div>
                        </div>

                        <!-- 6. Experience years -->
                        <div class="question-panel" data-group="experience_years">
                            <div class="d-flex justify-content-between flex-wrap">
                                <h2>Années de pratique</h2>
                                <span class="badge-optional">Optionnel</span>
                            </div>
                            <div class="choice-grid">
                                <?php foreach ($experienceYearsOptions as $opt): $checked = ($experienceYears === $opt); ?>
                                <label class="rating-card <?php echo $checked ? 'selected' : ''; ?>">
                                    <input type="radio" name="experience_years" value="<?php echo h($opt); ?>" <?php echo $checked ? 'checked' : ''; ?> />
                                    <?php echo h($opt); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- 7. Tools & materials -->
                        <div class="question-panel" data-group="tools_materials">
                            <div class="d-flex justify-content-between flex-wrap">
                                <h2>Matériaux & outils utilisés</h2>
                                <span class="badge-optional">Optionnel</span>
                            </div>
                            <div class="choice-grid">
                                <?php foreach ($toolsMaterialsOptions as $opt): $checked = in_array($opt, $toolsMaterials, true); ?>
                                <label class="choice-card <?php echo $checked ? 'selected' : ''; ?>">
                                    <input type="checkbox" name="tools_materials[]" value="<?php echo h($opt); ?>" <?php echo $checked ? 'checked' : ''; ?> />
                                    <?php echo h($opt); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- 8. Collaboration styles -->
                        <div class="question-panel" data-group="collaboration_styles">
                            <div class="d-flex justify-content-between flex-wrap">
                                <h2>Styles de collaboration</h2>
                                <span class="badge-optional">Optionnel</span>
                            </div>
                            <div class="choice-grid">
                                <?php foreach ($collaborationOptions as $opt): $checked = in_array($opt, $collaborationStyles, true); ?>
                                <label class="choice-card <?php echo $checked ? 'selected' : ''; ?>">
                                    <input type="checkbox" name="collaboration_styles[]" value="<?php echo h($opt); ?>" <?php echo $checked ? 'checked' : ''; ?> />
                                    <?php echo h($opt); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- 9. Availability -->
                        <div class="question-panel" data-group="availability">
                            <div class="d-flex justify-content-between flex-wrap">
                                <h2>Disponibilités</h2>
                                <span class="badge-optional">Optionnel</span>
                            </div>
                            <div class="choice-grid">
                                <?php foreach ($availabilityOptions as $opt): $checked = in_array($opt, $availability, true); ?>
                                <label class="choice-card <?php echo $checked ? 'selected' : ''; ?>">
                                    <input type="checkbox" name="availability[]" value="<?php echo h($opt); ?>" <?php echo $checked ? 'checked' : ''; ?> />
                                    <?php echo h($opt); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- 10. Location preferences -->
                        <div class="question-panel" data-group="location_preferences">
                            <div class="d-flex justify-content-between flex-wrap">
                                <h2>Zone d'intervention</h2>
                                <span class="badge-optional">Optionnel</span>
                            </div>
                            <div class="choice-grid">
                                <?php foreach ($locationOptions as $opt): $checked = in_array($opt, $locationPreferences, true); ?>
                                <label class="choice-card <?php echo $checked ? 'selected' : ''; ?>">
                                    <input type="checkbox" name="location_preferences[]" value="<?php echo h($opt); ?>" <?php echo $checked ? 'checked' : ''; ?> />
                                    <?php echo h($opt); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- 11. Market channels -->
                        <div class="question-panel" data-group="market_channels">
                            <div class="d-flex justify-content-between flex-wrap">
                                <h2>Canaux de vente / diffusion</h2>
                                <span class="badge-optional">Optionnel</span>
                            </div>
                            <div class="choice-grid">
                                <?php foreach ($marketOptions as $opt): $checked = in_array($opt, $marketChannels, true); ?>
                                <label class="choice-card <?php echo $checked ? 'selected' : ''; ?>">
                                    <input type="checkbox" name="market_channels[]" value="<?php echo h($opt); ?>" <?php echo $checked ? 'checked' : ''; ?> />
                                    <?php echo h($opt); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- 12. Work types -->
                        <div class="question-panel" data-group="work_types">
                            <div class="d-flex justify-content-between flex-wrap">
                                <h2>Types de travail recherchés</h2>
                                <span class="badge-optional">Optionnel</span>
                            </div>
                            <div class="choice-grid">
                                <?php foreach ($workTypeOptions as $opt): $checked = in_array($opt, $workTypes, true); ?>
                                <label class="choice-card <?php echo $checked ? 'selected' : ''; ?>">
                                    <input type="checkbox" name="work_types[]" value="<?php echo h($opt); ?>" <?php echo $checked ? 'checked' : ''; ?> />
                                    <?php echo h($opt); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- 13. Review panel -->
                        <div class="question-panel" data-group="review">
                            <h2>Récapitulatif de votre profil</h2>
                            <p>Vérifiez vos sélections avant validation définitive.</p>
                            <div class="review-list" id="reviewList"></div>
                            <div class="step-error"></div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between mt-4 gap-2">
                        <button type="button" id="prevBtn" class="btn btn-outline-secondary btn-custom" disabled><i class="fa-solid fa-arrow-left"></i> Retour</button>
                        <div class="d-flex gap-2">
                            <button type="submit" name="onboard_action" value="draft" class="btn btn-outline-secondary btn-custom" id="draftBtn"><i class="fa-regular fa-floppy-disk"></i> Sauvegarder brouillon</button>
                            <button type="submit" name="onboard_action" value="skip" class="btn btn-outline-dark btn-custom" id="skipBtn"><i class="fa-regular fa-clock"></i> Ignorer</button>
                            <button type="button" id="nextBtn" class="btn btn-success btn-custom">Suivant <i class="fa-solid fa-arrow-right"></i></button>
                        </div>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Toast container for notifications -->
<div class="toast-notification" id="toastContainer"></div>

<script>
    (function() {
        // ----- DOM elements -----
        const form = document.getElementById('onboardingForm');
        if (!form) return;
        const panels = Array.from(document.querySelectorAll('.question-panel'));
        let currentStep = 0;
        const totalSteps = panels.length;
        const stepLabels = <?php echo json_encode($stepLabels); ?>;

        // Build step labels / progress
        const stepsContainer = document.getElementById('stepLabelsContainer');
        const progressFill = document.getElementById('progressFill');
        
        function buildStepIndicators() {
            stepsContainer.innerHTML = '';
            for (let i = 0; i < totalSteps; i++) {
                const stepDiv = document.createElement('div');
                stepDiv.className = 'step-item';
                stepDiv.setAttribute('data-step-index', i);
                stepDiv.innerHTML = `
                    <div class="step-number"><span>${i+1}</span></div>
                    <span class="step-label">${stepLabels[i] || ''}</span>
                `;
                stepDiv.addEventListener('click', (e) => {
                    e.preventDefault();
                    goToStep(i);
                });
                stepsContainer.appendChild(stepDiv);
            }
        }
        
        function updateProgress() {
            const percent = ((currentStep) / (totalSteps - 1)) * 100;
            if (progressFill) progressFill.style.width = `${percent}%`;
            const items = document.querySelectorAll('.step-item');
            items.forEach((item, idx) => {
                item.classList.remove('active', 'completed');
                if (idx === currentStep) item.classList.add('active');
                else if (idx < currentStep) item.classList.add('completed');
            });
            const stepCounter = document.getElementById('stepCounter');
            if (stepCounter) stepCounter.textContent = `${currentStep+1} / ${totalSteps}`;
            const prevBtn = document.getElementById('prevBtn');
            if (prevBtn) prevBtn.disabled = (currentStep === 0);
            const nextBtn = document.getElementById('nextBtn');
            if (nextBtn) {
                if (currentStep === totalSteps - 1) {
                    nextBtn.innerHTML = 'Terminer <i class="fa-regular fa-circle-check"></i>';
                } else {
                    nextBtn.innerHTML = 'Suivant <i class="fa-solid fa-arrow-right"></i>';
                }
            }
            if (panels[currentStep] && panels[currentStep].getAttribute('data-group') === 'review') {
                buildReviewSummary();
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function showStep(stepIndex) {
            panels.forEach((p, idx) => {
                p.classList.toggle('is-active', idx === stepIndex);
            });
            updateProgress();
        }

        function clearStepError(stepElement) {
            const errDiv = stepElement.querySelector('.step-error');
            if (errDiv) errDiv.innerHTML = '';
        }
        
        function setStepError(stepElement, msg) {
            const errDiv = stepElement.querySelector('.step-error');
            if (errDiv) errDiv.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i> ${msg}`;
        }

        function hasAnyInterest() {
            const projects = document.querySelectorAll('input[name="project_interests[]"]:checked');
            const jobs = document.querySelectorAll('input[name="job_interests[]"]:checked');
            const formations = document.querySelectorAll('input[name="formation_interests[]"]:checked');
            return (projects.length + jobs.length + formations.length) > 0;
        }

        function validateCurrentStep() {
            const step = panels[currentStep];
            clearStepError(step);
            if (step.getAttribute('data-required') === 'true') {
                const inputs = step.querySelectorAll('input');
                let checked = false;
                inputs.forEach(inp => { if (inp.checked) checked = true; });
                if (!checked) {
                    const msg = step.getAttribute('data-msg') || 'Veuillez faire une sélection.';
                    setStepError(step, msg);
                    return false;
                }
            }
            if (step.getAttribute('data-group-exit-check') === 'true') {
                if (!hasAnyInterest()) {
                    setStepError(step, 'Choisissez au moins un sujet parmi les projets, missions ou formations.');
                    return false;
                }
            }
            return true;
        }

        function goToNext() {
            if (!validateCurrentStep()) return;
            if (currentStep + 1 < totalSteps) {
                currentStep++;
                showStep(currentStep);
            } else {
                // Submit final completion
                const hiddenComplete = document.createElement('input');
                hiddenComplete.type = 'hidden';
                hiddenComplete.name = 'onboard_action';
                hiddenComplete.value = 'complete';
                form.appendChild(hiddenComplete);
                showLoadingOverlay();
                form.submit();
            }
        }

        function goToPrev() {
            if (currentStep > 0) {
                currentStep--;
                showStep(currentStep);
            }
        }

        function goToStep(index) {
            if (index >= 0 && index < totalSteps) {
                if (index > currentStep && !validateCurrentStep()) return;
                currentStep = index;
                showStep(currentStep);
            }
        }

        function buildReviewSummary() {
            const container = document.getElementById('reviewList');
            if (!container) return;
            container.innerHTML = '';
            const sections = [
                { name: 'Domaines artisanaux', selector: 'input[name="craft_focus[]"]:checked' },
                { name: 'Projets', selector: 'input[name="project_interests[]"]:checked' },
                { name: 'Emplois', selector: 'input[name="job_interests[]"]:checked' },
                { name: 'Formations', selector: 'input[name="formation_interests[]"]:checked' },
                { name: "Niveau d'expérience", selector: 'input[name="experience_level"]:checked' },
                { name: 'Années pratique', selector: 'input[name="experience_years"]:checked' },
                { name: 'Matériaux', selector: 'input[name="tools_materials[]"]:checked' },
                { name: 'Collaboration', selector: 'input[name="collaboration_styles[]"]:checked' },
                { name: 'Disponibilité', selector: 'input[name="availability[]"]:checked' },
                { name: 'Zone', selector: 'input[name="location_preferences[]"]:checked' },
                { name: 'Canaux de vente', selector: 'input[name="market_channels[]"]:checked' },
                { name: 'Types travail', selector: 'input[name="work_types[]"]:checked' }
            ];
            let hasData = false;
            sections.forEach(section => {
                const checked = document.querySelectorAll(section.selector);
                if (checked.length > 0) {
                    hasData = true;
                    const values = Array.from(checked).map(cb => cb.closest('label')?.innerText.trim() || cb.value);
                    const sectionDiv = document.createElement('div');
                    sectionDiv.className = 'mb-3';
                    sectionDiv.innerHTML = `<strong>${section.name}</strong><div class="review-list mt-1">${values.map(v => `<span class="summary-pill">${escapeHtml(v)}</span>`).join('')}</div>`;
                    container.appendChild(sectionDiv);
                }
            });
            if (!hasData) {
                container.innerHTML = '<div class="alert alert-info">Aucune information sélectionnée. Vous pouvez toujours compléter plus tard.</div>';
            }
        }

        function escapeHtml(str) {
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }

        function showLoadingOverlay() {
            let overlay = document.querySelector('.loading-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'loading-overlay';
                overlay.innerHTML = '<div class="spinner-border text-light spinner-border-custom" role="status"><span class="visually-hidden">Chargement...</span></div>';
                document.body.appendChild(overlay);
            }
            overlay.style.display = 'flex';
        }

        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} alert-dismissible fade show shadow`;
            toast.innerHTML = `${message} <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>`;
            toastContainer.appendChild(toast);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // Confirm skip action
        document.getElementById('skipBtn')?.addEventListener('click', (e) => {
            if (confirm('Ignorer complètement l\'onboarding ? Vous pourrez le compléter plus tard depuis votre tableau de bord.')) {
                showLoadingOverlay();
                const skipInput = document.createElement('input');
                skipInput.type = 'hidden';
                skipInput.name = 'onboard_action';
                skipInput.value = 'skip';
                form.appendChild(skipInput);
                form.submit();
            } else {
                e.preventDefault();
            }
        });

        // Draft confirmation toast
        document.getElementById('draftBtn')?.addEventListener('click', (e) => {
            showToast('Sauvegarde du brouillon en cours...', 'info');
            showLoadingOverlay();
            const draftInput = document.createElement('input');
            draftInput.type = 'hidden';
            draftInput.name = 'onboard_action';
            draftInput.value = 'draft';
            form.appendChild(draftInput);
            form.submit();
        });

        // Update selection highlights and clear errors on change
        document.addEventListener('change', (e) => {
            if (e.target.matches('input[type="checkbox"], input[type="radio"]')) {
                const card = e.target.closest('.choice-card, .rating-card');
                if (card) {
                    if (e.target.type === 'checkbox') {
                        card.classList.toggle('selected', e.target.checked);
                    } else {
                        // radio: unselect siblings
                        const group = card.closest('.choice-grid');
                        if (group) {
                            group.querySelectorAll('.rating-card').forEach(c => c.classList.remove('selected'));
                        }
                        card.classList.add('selected');
                    }
                }
                clearStepError(panels[currentStep]);
            }
        });

        // Final form validation before submit for complete action
        form.addEventListener('submit', (e) => {
            const submitter = e.submitter;
            if (submitter && (submitter.value === 'skip' || submitter.value === 'draft')) return;
            const craftOk = document.querySelectorAll('input[name="craft_focus[]"]:checked').length > 0;
            const expOk = document.querySelector('input[name="experience_level"]:checked') !== null;
            const interestOk = hasAnyInterest();
            if (!craftOk) {
                e.preventDefault();
                const craftStep = panels.find(p => p.getAttribute('data-group') === 'craft_focus');
                if (craftStep) { setStepError(craftStep, 'Sélectionnez au moins un domaine artisanal.'); currentStep = panels.indexOf(craftStep); showStep(currentStep); }
            } else if (!interestOk) {
                e.preventDefault();
                const interestStep = panels.find(p => p.getAttribute('data-group') === 'formation_interests');
                if (interestStep) { setStepError(interestStep, 'Choisissez au moins un intérêt (projet, emploi, formation).'); currentStep = panels.indexOf(interestStep); showStep(currentStep); }
            } else if (!expOk) {
                e.preventDefault();
                const expStep = panels.find(p => p.getAttribute('data-group') === 'experience_level');
                if (expStep) { setStepError(expStep, 'Indiquez votre niveau d\'expérience.'); currentStep = panels.indexOf(expStep); showStep(currentStep); }
            } else {
                showLoadingOverlay();
            }
        });

        // Initialization
        buildStepIndicators();
        document.querySelectorAll('input:checked').forEach(inp => {
            const card = inp.closest('.choice-card, .rating-card');
            if (card) card.classList.add('selected');
        });
        showStep(0);
        
        // Assign global buttons
        document.getElementById('nextBtn')?.addEventListener('click', goToNext);
        document.getElementById('prevBtn')?.addEventListener('click', goToPrev);
    })();
</script>
<?php if ($success): ?>
<script>
    setTimeout(() => window.location.href = <?php echo json_encode($redirectUrl); ?>, 1800);
</script>
<?php endif; ?>
</body>
</html>