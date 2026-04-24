<?php
/**
 * Vue : Liste de tous les projets (avec catégorie via INNER JOIN)
 * Fichier : views/projets/index.php
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projets — CraftLink</title>
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        body { font-family: 'DM Sans', sans-serif; background: #f5f3ef; margin: 0; }
        .container { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
        h1 { font-family: 'Playfair Display', serif; color: #2c2c2c; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
        .btn { display: inline-block; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; cursor: pointer; border: none; font-size: 0.95rem; }
        .btn-primary { background: #b87333; color: #fff; }
        .btn-primary:hover { background: #9a5e28; }
        .btn-info    { background: #4a90d9; color: #fff; }
        .btn-info:hover { background: #3578c0; }
        .btn-danger  { background: #d94f4f; color: #fff; }
        .btn-danger:hover { background: #b03939; }
        .btn-sm { padding: 6px 12px; font-size: 0.85rem; }
        .alert { padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background: #d4edda; color: #1a5c2a; border: 1px solid #b8dfc4; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        thead { background: #2c2c2c; color: #fff; }
        th, td { padding: 14px 16px; text-align: left; }
        tbody tr:nth-child(even) { background: #faf8f5; }
        tbody tr:hover { background: #f0ebe3; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; }
        .badge-attente { background: #fff3cd; color: #856404; }
        .badge-cours   { background: #cff4fc; color: #0a5e75; }
        .badge-termine { background: #d4edda; color: #1a5c2a; }
        .actions { display: flex; gap: 8px; }
        .nav-back { margin-bottom: 20px; }
        .nav-back a { color: #b87333; text-decoration: none; font-weight: 500; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="container">

    <div class="nav-back">
        <a href="index.php?page=dashboard">← Retour au tableau de bord</a>
    </div>

    <div class="toolbar">
        <h1>📁 Gestion des Projets</h1>
        <div style="display:flex; gap:10px;">
            <a href="index.php?page=projets_recherche" class="btn btn-info">🔍 Recherche par catégorie</a>
            <a href="index.php?page=projets_ajouter" class="btn btn-primary">+ Ajouter un projet</a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?php
            $msgs = [
                'ajoute'   => '✅ Projet ajouté avec succès.',
                'modifie'  => '✅ Projet modifié avec succès.',
                'supprime' => '✅ Projet supprimé avec succès.',
            ];
            echo $msgs[$_GET['success']] ?? '✅ Opération réussie.';
            ?>
        </div>
    <?php endif; ?>

    <?php if (empty($projets)): ?>
        <p style="text-align:center; color:#888; padding:40px;">Aucun projet pour le moment.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Titre</th>
                <th>Catégorie</th>
                <th>Budget (TND)</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($projets as $projet): ?>
            <tr>
                <td><?= (int) $projet['id_projet'] ?></td>
                <td><?= htmlspecialchars($projet['titre']) ?></td>
                <td><?= htmlspecialchars($projet['nom_categorie']) ?></td>
                <td><?= number_format((float) $projet['budget'], 2, ',', ' ') ?> TND</td>
                <td>
                    <?php
                    $badges = [
                        'en_attente' => ['label' => 'En attente', 'class' => 'badge-attente'],
                        'en_cours'   => ['label' => 'En cours',   'class' => 'badge-cours'],
                        'termine'    => ['label' => 'Terminé',    'class' => 'badge-termine'],
                    ];
                    $b = $badges[$projet['statut']] ?? ['label' => $projet['statut'], 'class' => ''];
                    ?>
                    <span class="badge <?= $b['class'] ?>"><?= $b['label'] ?></span>
                </td>
                <td>
                    <div class="actions">
                        <a href="index.php?page=projets_show&id=<?= (int) $projet['id_projet'] ?>" class="btn btn-secondary btn-sm">👁️ Voir</a>
                        <a href="index.php?page=projets_modifier&id=<?= (int) $projet['id_projet'] ?>" class="btn btn-info btn-sm">✏️ Modifier</a>
                        <a href="index.php?page=projets_supprimer&id=<?= (int) $projet['id_projet'] ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Supprimer ce projet ?')">🗑 Supprimer</a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

</div>
</body>
</html>
