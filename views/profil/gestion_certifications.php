<?php
// views/profil/gestion_certifications.php
// Variables: $certifications
$baseUrl = app_url();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des certifications</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(app_url('/public/assets/css/profile.css')) ?>">

</head>
<body>
<h1>Gestion des certification</h1>
 <a href="<?= htmlspecialchars(app_url('/profil')) ?>">➕ Ajouter</a>
<div class="section-card-custom">
    <div class="section-title-custom">Certifications</div>

    <?php if (!empty($certifications)): ?>
        <?php foreach ($certifications as $cert): ?>
            <div class="item-card">
                <div class="item-info">
                    <div class="item-title"><?= htmlspecialchars($cert['nom_certification']) ?></div>
                    <div class="item-desc">Niveau: <?= $cert['niveau'] ?>%</div>
                </div>

                <div class="item-actions">
                    <form method="post" action="<?= htmlspecialchars(app_url('/profil/deleteCertification')) ?>">
                        <input type="hidden" name="id" value="<?= $cert['id_certification'] ?>">
                        <button>🗑️</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="empty-text">Aucune certification</p>
    <?php endif; ?>
</div>

</body>
</html>
