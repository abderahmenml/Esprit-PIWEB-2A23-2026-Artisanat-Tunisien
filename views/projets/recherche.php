<?php
/**
 * Vue : Recherche de projets par catégorie (jointure projet ↔ categorie)
 * Fichier : views/projets/recherche.php
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche par catégorie — CraftLink</title>
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        body { font-family: 'DM Sans', sans-serif; background: #f5f3ef; margin: 0; }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        h1 { font-family: 'Playfair Display', serif; color: #2c2c2c; margin-bottom: 8px; }
        .subtitle { color: #888; margin-bottom: 30px; }
        .search-card { background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 32px; }
        .form-group { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
        label { font-weight: 600; color: #2c2c2c; font-size: 1rem; white-space: nowrap; }
        select { padding: 10px 14px; border: 2px solid #e0d9cf; border-radius: 8px; font-size: 0.95rem; min-width: 220px; background: #faf8f5; color: #2c2c2c; cursor: pointer; }
        select:focus { outline: none; border-color: #b87333; }
        .btn { display: inline-block; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-size: 0.95rem; }
        .btn-primary { background: #b87333; color: #fff; }
        .btn-primary:hover { background: #9a5e28; }
        .btn-secondary { background: #e0d9cf; color: #2c2c2c; text-decoration: none; }
        .results-title { font-family: 'Playfair Display', serif; color: #2c2c2c; margin-bottom: 16px; font-size: 1.3rem; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        thead { background: #b87333; color: #fff; }
        th, td { padding: 14px 16px; text-align: left; }
        tbody tr:nth-child(even) { background: #faf8f5; }
        tbody tr:hover { background: #f0ebe3; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; }
        .badge-attente { background: #fff3cd; color: #856404; }
        .badge-cours   { background: #cff4fc; color: #0a5e75; }
        .badge-termine { background: #d4edda; color: #1a5c2a; }
        .no-result { text-align: center; padding: 40px; color: #888; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .nav-back { margin-bottom: 20px; }
        .nav-back a { color: #b87333; text-decoration: none; font-weight: 500; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="container">

    <div class="nav-back">
        <a href="index.php?page=projets">← Retour à la liste des projets</a>
    </div>

    <h1>🔍 Recherche de projets par catégorie</h1>
    <p class="subtitle">Sélectionnez une catégorie pour afficher les projets correspondants (jointure SQL).</p>

    <div class="search-card">
        <form action="index.php?page=projets_recherche" method="POST">
            <div class="form-group">
                <label for="id_categorie">Sélectionnez une catégorie :</label>
                <select name="id_categorie" id="id_categorie">
                    <option value="">-- Choisir --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id_categorie'] ?>"
                            <?= ((int)($idCategorie ?? 0) === (int) $cat['id_categorie']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="rechercher" class="btn btn-primary">Rechercher</button>
                <a href="index.php?page=projets_recherche" class="btn btn-secondary">Réinitialiser</a>
            </div>
            <?php if ($idCategorie !== null && empty($projets) && isset($_POST['rechercher'])): ?>
                <p style="color:#c0392b; margin-top:12px; font-weight:500;">⚠️ Veuillez sélectionner une catégorie valide.</p>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($idCategorie !== null && isset($_POST['rechercher'])): ?>
        <h2 class="results-title">Projets correspondants au genre sélectionné :</h2>

        <?php if (empty($projets)): ?>
            <div class="no-result">
                <p>😕 Aucun projet trouvé pour cette catégorie.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Description</th>
                        <th>Budget (TND)</th>
                        <th>Statut</th>
                        <th>Catégorie</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($projets as $projet): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($projet['titre']) ?></strong></td>
                        <td><?= htmlspecialchars(mb_substr($projet['description'], 0, 80)) ?>…</td>
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
                        <td><?= htmlspecialchars($projet['nom_categorie']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>

</div>
</body>
</html>
