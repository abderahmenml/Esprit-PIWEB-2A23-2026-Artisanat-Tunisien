<?php
/**
 * Vue : Formulaire de modification d'un projet
 * Fichier : views/projets/modifier.php
 * Validation : côté serveur PHP uniquement (pas de HTML5 required/pattern)
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le projet — CraftLink</title>
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        body { font-family: 'DM Sans', sans-serif; background: #f5f3ef; margin: 0; }
        .container { max-width: 700px; margin: 40px auto; padding: 0 20px; }
        h1 { font-family: 'Playfair Display', serif; color: #2c2c2c; margin-bottom: 6px; }
        .subtitle { color: #888; margin-bottom: 28px; font-size: 0.93rem; }
        .card { background: #fff; border-radius: 14px; padding: 36px; box-shadow: 0 2px 16px rgba(0,0,0,0.08); }
        .form-group { margin-bottom: 22px; }
        .form-group label { display: block; font-weight: 600; color: #2c2c2c; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 11px 14px; border: 2px solid #e0d9cf; border-radius: 8px; font-size: 0.95rem; font-family: inherit; background: #faf8f5; box-sizing: border-box; transition: border-color 0.2s; }
        .form-control:focus { outline: none; border-color: #b87333; }
        .form-control.is-invalid { border-color: #d94f4f; }
        .error-msg { color: #d94f4f; font-size: 0.85rem; margin-top: 5px; font-weight: 500; }
        .alert-danger { background: #fde8e8; color: #7b1d1d; border: 1px solid #f5c2c2; border-radius: 8px; padding: 14px 18px; margin-bottom: 22px; }
        textarea.form-control { min-height: 110px; resize: vertical; }
        .btn { display: inline-block; padding: 11px 26px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-size: 0.97rem; font-family: inherit; }
        .btn-primary { background: #b87333; color: #fff; }
        .btn-primary:hover { background: #9a5e28; }
        .btn-secondary { background: #e0d9cf; color: #2c2c2c; text-decoration: none; margin-left: 12px; }
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

    <h1>✏️ Modifier un Projet</h1>
    <p class="subtitle">Projet #<?= (int) $projet['id_projet'] ?> — Catégorie actuelle : <strong><?= htmlspecialchars($projet['nom_categorie']) ?></strong></p>

    <div class="card">
        <?php if (!empty($errors['global'])): ?>
            <div class="alert-danger"><?= htmlspecialchars($errors['global']) ?></div>
        <?php endif; ?>

        <form action="index.php?page=projets_modifier&id=<?= (int) $projet['id_projet'] ?>" method="POST">

            <div class="form-group">
                <label for="titre">Titre du projet</label>
                <input type="text"
                       id="titre"
                       name="titre"
                       class="form-control <?= isset($errors['titre']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($projet['titre']) ?>">
                <?php if (isset($errors['titre'])): ?>
                    <span class="error-msg"><?= htmlspecialchars($errors['titre']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description"
                          name="description"
                          class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"><?= htmlspecialchars($projet['description']) ?></textarea>
                <?php if (isset($errors['description'])): ?>
                    <span class="error-msg"><?= htmlspecialchars($errors['description']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="budget">Budget (TND)</label>
                <input type="text"
                       id="budget"
                       name="budget"
                       class="form-control <?= isset($errors['budget']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($projet['budget']) ?>">
                <?php if (isset($errors['budget'])): ?>
                    <span class="error-msg"><?= htmlspecialchars($errors['budget']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="statut">Statut</label>
                <select id="statut" name="statut"
                        class="form-control <?= isset($errors['statut']) ? 'is-invalid' : '' ?>">
                    <option value="en_attente" <?= $projet['statut'] === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                    <option value="en_cours"   <?= $projet['statut'] === 'en_cours'   ? 'selected' : '' ?>>En cours</option>
                    <option value="termine"    <?= $projet['statut'] === 'termine'    ? 'selected' : '' ?>>Terminé</option>
                </select>
                <?php if (isset($errors['statut'])): ?>
                    <span class="error-msg"><?= htmlspecialchars($errors['statut']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="id_categorie">Catégorie</label>
                <select id="id_categorie" name="id_categorie"
                        class="form-control <?= isset($errors['id_categorie']) ? 'is-invalid' : '' ?>">
                    <option value="">-- Sélectionner une catégorie --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id_categorie'] ?>"
                            <?= ((int) $projet['id_categorie'] === (int) $cat['id_categorie']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['id_categorie'])): ?>
                    <span class="error-msg"><?= htmlspecialchars($errors['id_categorie']) ?></span>
                <?php endif; ?>
            </div>

            <div style="margin-top:28px;">
                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                <a href="index.php?page=projets" class="btn btn-secondary">Annuler</a>
            </div>

        </form>
    </div>

</div>
</body>
</html>
