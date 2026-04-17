<?php
// views/dashboard/index.php
// Variables: $user, $stats, $competences, $completion
$baseUrl = app_url();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord — حرفة Tunisie</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars(app_url('/public/css/style.css')) ?>">
    <script src="<?= htmlspecialchars(app_url('/public/js/index.js')) ?>" defer></script>
</head>
<body>

<header>
    <div class="logo">
        <span class="ar">حرفة</span>
        <span style="color:#aaa;font-size:.8rem;font-weight:400;margin-left:2px">Tunisie</span>
    </div>
   <nav>
    <a href="<?= htmlspecialchars(app_url('/dashboard')) ?>">🏠 Accueil</a>
    <a href="<?= htmlspecialchars(app_url('/profil')) ?>">Mon Profil</a>
    <a href="<?= htmlspecialchars(app_url('/dashboard/annuaire')) ?>">👥 Annuaire</a>
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
                    <a href="<?= htmlspecialchars(app_url('/profil')) ?>" style="color: var(--caramel);">⟳ Compléter</a>
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
            <a href="<?= htmlspecialchars(app_url('/profil')) ?>" class="quick-btn">✏️ Modifier mon profil</a>
            <a href="#" class="quick-btn">💡 Créer un projet</a>
            <a href="#" class="quick-btn">🎓 Explorer formations</a>
        </div>
    </div>

</div>

</body>
</html>
