<?php
// Variables: $competences
$baseUrl = app_url();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des compétences</title>

    <link rel="stylesheet" href="<?= htmlspecialchars(app_url('/public/assets/css/profile.css')) ?>">
</head>
<body>

<h1>Gestion des compétences</h1>
<a href="<?= htmlspecialchars(app_url('/profil')) ?>">➕ Ajouter</a>

<div class="section-card-custom">
    <div class="section-title-custom">Compétences</div>
    <form method="post" action="<?= htmlspecialchars(app_url('/profil/addCompetence')) ?>" style="display:grid;gap:.75rem;margin-bottom:1.2rem;padding:1rem;background:#f8f5f0;border-radius:1rem;">
        <label>
            Compétence existante
            <select name="competence_catalog_choice" style="width:100%;padding:.5rem;border:1px solid #e0d6c3;border-radius:.5rem;background:#fff7ee;">
                <option value="">-- Choisir dans le catalogue --</option>
                <?php foreach (($competenceCatalog ?? []) as $catalog): ?>
                    <?php $catalogChoiceValue = !empty($catalog['id_competence_catalog']) ? 'id:' . (int)$catalog['id_competence_catalog'] : 'name:' . (string)($catalog['nom_competence'] ?? ''); ?>
                    <option value="<?= htmlspecialchars($catalogChoiceValue, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars((string)($catalog['nom_competence'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Nouvelle compétence
            <input type="text" name="nom" maxlength="80" placeholder="Saisir si absent du catalogue" style="width:100%;padding:.5rem;border:1px solid #e0d6c3;border-radius:.5rem;background:#fff7ee;">
        </label>
        <label>
            Description
            <input type="text" name="description" maxlength="500" style="width:100%;padding:.5rem;border:1px solid #e0d6c3;border-radius:.5rem;background:#fff7ee;">
        </label>
        <label>
            Niveau
            <input type="range" min="0" max="100" value="50" name="niveau" style="width:100%;accent-color:var(--marron);">
        </label>
        <button type="submit">Ajouter la compétence</button>
    </form>
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
