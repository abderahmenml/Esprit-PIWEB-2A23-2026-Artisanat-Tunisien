<?php
// views/dashboard/annuaire.php
// Variables: $profils, $totalPages, $page
$baseUrl = app_url();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Annuaire des professionnels — حرفة Tunisie</title>
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
    <a href="<?= htmlspecialchars(app_url('/profil')) ?>">🪪 Mon Profil</a>
    <a href="<?= htmlspecialchars(app_url('/dashboard/annuaire')) ?>">👥 Annuaire</a>
    <a href="#">💡 Projets</a>
    <a href="#">🎓 Formations</a>
    <a href="#">📈 Investissement</a>
    <button class="btn-logout" onclick="handleLogout()">Déconnexion</button>
</nav>
</header>
<div class="page" style="grid-template-columns:1fr; max-width:1200px;">
    <h1>👥 Annuaire des professionnels</h1>
    <div class="profils-grid">
        <?php foreach ($profils as $p): ?>
            <div class="profile-card-annuaire">
                <div class="avatar-annuaire"><?= strtoupper(substr($p['prenom'],0,1).substr($p['nom'],0,1)) ?></div>
                <h3><?= htmlspecialchars($p['prenom'].' '.$p['nom']) ?></h3>
                <p class="specialite"><?= htmlspecialchars($p['specialite'] ?? 'Artisan') ?></p>
                <p class="ville">📍 <?= htmlspecialchars($p['ville'] ?? 'Tunisie') ?></p>
                <a href="<?= htmlspecialchars(app_url('/profil')) ?>" class="btn-voir">Voir le profil →</a>
            </div>
        <?php endforeach; ?>
    </div>
    <!-- Pagination -->
    <div class="pagination">
        <?php for ($i=1; $i<=$totalPages; $i++): ?>
            <a href="<?= htmlspecialchars(app_url('/dashboard/annuaire')) ?>?page=<?= $i ?>" class="page-link <?= $i==$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>
</body>
</html>
