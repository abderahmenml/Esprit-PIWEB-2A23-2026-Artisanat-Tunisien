<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$pdo = getPDO();
$user_id = $_SESSION['user_id'];

$user = getUserById($user_id);
$stats = getStats($user_id);
$competences = getUserCompetences($user_id);

// Calcul du pourcentage de complétion
$completion = 0;
$fields = [
    'nom' => $user['nom'] ?? '',
    'prenom' => $user['prenom'] ?? '',
    'email' => $user['email'] ?? '',
    'specialite' => $user['specialite'] ?? '',
    'bio' => $user['bio'] ?? '',
    'competences' => count($competences) > 0
];
$filled = 0;
foreach($fields as $field => $value) {
    if(!empty($value)) $filled++;
}
$completion = round(($filled / count($fields)) * 100);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord — حرفة Tunisie</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="index.js" defer></script>
</head>
<body>

<header>
    <div class="logo">
        <span class="ar">حرفة</span>
        <span style="color:#aaa;font-size:.8rem;font-weight:400;margin-left:2px">Tunisie</span>
    </div>
   <nav>
    <a href="dashboard.php">🏠 Accueil</a>
    <a href="profil_professionnel.php">🪪 Mon Profil</a>
    <a href="annuaire.php">👥 Annuaire</a>   <!-- NOUVEAU LIEN -->
    <a href="#">💡 Projets</a>
    <a href="#">🎓 Formations</a>
    <a href="#">📈 Investissement</a>
    <button class="btn-logout" onclick="handleLogout()">Déconnexion</button>
</nav>
</header>

<div class="page" style="max-width: 1200px; margin: 0 auto; padding: 2rem;">
    
    <div class="welcome-card">
        <div class="welcome-title">👋 Bonjour, <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></div>
        <div class="welcome-sub">
            <?php if($user['specialite']): ?>
                <?= htmlspecialchars($user['specialite']) ?> · 
            <?php endif; ?>
            Bienvenue sur votre tableau de bord
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="stat-dashboard-card">
            <div class="stat-icon">📊</div>
            <div class="stat-value"><?= $stats['projets'] ?></div>
            <div class="stat-label">Projets</div>
        </div>
        <div class="stat-dashboard-card">
            <div class="stat-icon">⭐</div>
            <div class="stat-value"><?= $stats['note'] ?></div>
            <div class="stat-label">Note moyenne</div>
        </div>
        <div class="stat-dashboard-card">
            <div class="stat-icon">👥</div>
            <div class="stat-value"><?= $stats['mentores'] ?></div>
            <div class="stat-label">Mentorés</div>
        </div>
        <div class="stat-dashboard-card">
            <div class="stat-icon">🎯</div>
            <div class="stat-value"><?= count($competences) ?></div>
            <div class="stat-label">Compétences</div>
        </div>
    </div>

    <div class="section-card">
        <div class="section-title-dashboard">📋 Complétion de votre profil</div>
        <div class="progress-section">
            <div style="display: flex; justify-content: space-between;">
                <span>Profil complété à <?= $completion ?>%</span>
                <?php if($completion < 80): ?>
                    <a href="profil_complet.php" style="color: var(--caramel);">⟳ Compléter</a>
                <?php endif; ?>
            </div>
            <div class="progress-bar-dash">
                <div class="progress-fill-dash" style="width: <?= $completion ?>%;"></div>
            </div>
        </div>
    </div>

    <div class="section-card">
        <div class="section-title-dashboard">⚡ Actions rapides</div>
        <div class="quick-actions">
            <a href="profil_complet.php" class="quick-btn">✏️ Modifier mon profil</a>
            <a href="#" class="quick-btn">💡 Créer un projet</a>
            <a href="#" class="quick-btn">🎓 Explorer formations</a>
        </div>
    </div>

</div>

</body>
</html>