<?php
// Variables: $competences
$baseUrl = app_url();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des compétences</title>
    
    <link rel="stylesheet" href="<?= htmlspecialchars(app_url('/public/css/style.css')) ?>">
</head>
<body>

<h1>Gestion des compétences</h1>
<a href="<?= htmlspecialchars(app_url('/profil')) ?>">➕ Ajouter</a>

<table border="1">
<tr>
<th>Ordre</th><th>Nom</th><th>Description</th><th>Niveau</th><th>Actions</th>
</tr>
<div class="section-card-custom">
    <div class="section-title-custom">Compétences</div>
<?php if (!empty($competences)): ?>
        <?php foreach ($competences as $c): ?>
            <div class="item-card">
                <div class="item-info">
                    <div class="item-title"><?= htmlspecialchars($c['nom_competence']) ?></div>
                    <div class="item-desc"><?= htmlspecialchars($c['description']) ?></div>
                    <div class="item-desc">Niveau: <?= $c['niveau'] ?>%</div>
                </div>

                <div class="item-actions">
                    <form method="post" action="<?= htmlspecialchars(app_url('/profil/deleteCompetence')) ?>">
                        <input type="hidden" name="id" value="<?= $c['id_competence'] ?>">
                        <button>🗑️</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="empty-text">Aucune compétence renseignée</p>
    <?php endif; ?>
</div>

</body>
</html>