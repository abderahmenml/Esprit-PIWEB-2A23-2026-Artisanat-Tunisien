<?php
/**
 * Vue : Détail d'un projet avec jointure catégorie
 * Fichier : views/projets/show.php
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détail du projet — CraftLink</title>
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        body { font-family: 'DM Sans', sans-serif; background: #f5f3ef; margin: 0; }
        .container { max-width: 780px; margin: 40px auto; padding: 0 20px; }
        h1 { font-family: 'Playfair Display', serif; color: #2c2c2c; margin-bottom: 6px; }
        .subtitle { color: #888; margin-bottom: 24px; }
        .card { background: #fff; border-radius: 14px; padding: 34px; box-shadow: 0 2px 16px rgba(0,0,0,0.08); }
        .field { margin-bottom: 18px; }
        .label { display: block; font-weight: 700; color: #2c2c2c; margin-bottom: 8px; }
        .value { padding: 16px; border-radius: 12px; background: #f8f4ee; color: #3d3d3d; line-height: 1.6; }
        .badge { display: inline-block; padding: 6px 12px; border-radius: 999px; font-weight: 700; font-size: 0.9rem; }
        .badge-attente { background: #fff3cd; color: #856404; }
        .badge-cours   { background: #cff4fc; color: #0a5e75; }
        .badge-termine { background: #d4edda; color: #1a5c2a; }
        .actions { margin-top: 30px; display: flex; gap: 12px; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 12px 24px; border-radius: 10px; border: none; font-weight: 700; text-decoration: none; color: #fff; background: #b87333; }
        .btn:hover { background: #9a5e28; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #555d64; }
        .nav-back { margin-bottom: 20px; }
        .nav-back a { color: #b87333; text-decoration: none; font-weight: 500; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="container">
    <div class="nav-back">
        <a href="index.php?page=projets">← Retour à la liste</a>
    </div>
    <h1>📌 Détail du projet</h1>
    <p class="subtitle">Affichage du projet avec la catégorie liée via la jointure SQL.</p>
    <div class="card">
        <div class="field">
            <span class="label">Titre</span>
            <div class="value"><?= htmlspecialchars($projet['titre']) ?></div>
        </div>
        <div class="field">
            <span class="label">Description</span>
            <div class="value"><?= nl2br(htmlspecialchars($projet['description'])) ?></div>
        </div>
        <div class="field">
            <span class="label">Budget</span>
            <div class="value"><?= number_format((float) $projet['budget'], 2, ',', ' ') ?> TND</div>
        </div>
        <div class="field">
            <span class="label">Statut</span>
            <?php
            $badges = [
                'en_attente' => ['label' => 'En attente', 'class' => 'badge-attente'],
                'en_cours'   => ['label' => 'En cours',   'class' => 'badge-cours'],
                'termine'    => ['label' => 'Terminé',    'class' => 'badge-termine'],
            ];
            $b = $badges[$projet['statut']] ?? ['label' => htmlspecialchars($projet['statut']), 'class' => 'badge-attente'];
            ?>
            <div class="value"><span class="badge <?= $b['class'] ?>"><?= $b['label'] ?></span></div>
        </div>
        <div class="field">
            <span class="label">Catégorie</span>
            <div class="value"><?= htmlspecialchars($projet['nom_categorie']) ?></div>
        </div>
        <div class="actions">
            <a href="index.php?page=projets_modifier&id=<?= (int) $projet['id_projet'] ?>" class="btn">Modifier</a>
            <a href="index.php?page=projets_supprimer&id=<?= (int) $projet['id_projet'] ?>" class="btn btn-secondary" onclick="return confirm('Supprimer ce projet ?');">Supprimer</a>
        </div>
    </div>
</div>
</body>
</html>
