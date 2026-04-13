<?php
// annuaire.php
require_once 'config.php';
$pdo = getPDO();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 9;
$offset = ($page - 1) * $limit;

// Compter total
$stmt = $pdo->query("SELECT COUNT(*) FROM user u LEFT JOIN profil_professionnel p ON u.id_user = p.id_user");
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $limit);

// Récupérer les profils avec pagination
$sql = "SELECT u.id_user, u.nom, u.prenom, u.email, p.specialite, p.bio, p.ville, p.portfolio
        FROM user u
        LEFT JOIN profil_professionnel p ON u.id_user = p.id_user
        ORDER BY u.date_creation DESC
        LIMIT $limit OFFSET $offset";
$profils = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Annuaire des professionnels — حرفة Tunisie</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>...</header> <!-- reprendre le même header -->
<div class="page" style="grid-template-columns:1fr; max-width:1200px;">
    <h1>👥 Annuaire des professionnels</h1>
    <div class="profils-grid">
        <?php foreach ($profils as $p): ?>
            <div class="profile-card-annuaire">
                <div class="avatar-annuaire"><?= strtoupper(substr($p['prenom'],0,1).substr($p['nom'],0,1)) ?></div>
                <h3><?= htmlspecialchars($p['prenom'].' '.$p['nom']) ?></h3>
                <p class="specialite"><?= htmlspecialchars($p['specialite'] ?? 'Artisan') ?></p>
                <p class="ville">📍 <?= htmlspecialchars($p['ville'] ?? 'Tunisie') ?></p>
                <a href="profil.php?id=<?= $p['id_user'] ?>" class="btn-voir">Voir le profil →</a>
            </div>
        <?php endforeach; ?>
    </div>
    <!-- Pagination -->
    <div class="pagination">
        <?php for ($i=1; $i<=$totalPages; $i++): ?>
            <a href="?page=<?= $i ?>" class="page-link <?= $i==$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>
</body>
</html>