<?php
$baseAdminUrl = app_url('/admin');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Competences</title>
    <style>
        body { font-family: "Segoe UI", sans-serif; margin: 0; padding: 1.4rem; background: #f8f2e6; color: #3b2314; }
        .wrap { max-width: 1100px; margin: 0 auto; }
        .top { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem; }
        .card { background: #fff; border: 1px solid rgba(139,90,58,.2); border-radius: 10px; padding: 1rem; margin-bottom: 1rem; }
        .alert { padding: .7rem .8rem; border-radius: 8px; margin-bottom: .8rem; }
        .alert.error { background: #fdeceb; color: #7d221f; border: 1px solid #f0b9b7; }
        .alert.success { background: #eaf8ee; color: #1f5a30; border: 1px solid #a6d7b2; }
        .btn { border: 1px solid rgba(139,90,58,.25); border-radius: 8px; background: #fff; color: #3b2314; padding: .45rem .8rem; text-decoration: none; cursor: pointer; }
        .btn.primary { background: #2e6b3e; color: #fff; border-color: #2e6b3e; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #f0e4d4; padding: .6rem; text-align: left; vertical-align: top; }
        th { font-size: .8rem; text-transform: uppercase; color: #6d5947; }
        input, textarea { width: 100%; border: 1px solid rgba(139,90,58,.22); border-radius: 6px; padding: .45rem .55rem; }
        textarea { min-height: 40px; resize: vertical; }
        .grid { display: grid; grid-template-columns: 1fr 2fr; gap: 1rem; }
        @media (max-width: 900px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <h1 style="margin:0;">Administration des competences</h1>
        <a class="btn" href="<?= htmlspecialchars($baseAdminUrl) ?>">Retour admin</a>
    </div>

    <?php if (!empty($flashAdmin['msg'])): ?>
        <div class="alert <?= (($flashAdmin['type'] ?? '') === 'success') ? 'success' : 'error' ?>">
            <?= htmlspecialchars((string)$flashAdmin['msg']) ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg !== ''): ?>
        <div class="alert error"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <h3 style="margin-top:0;">Ajouter une competence</h3>
            <form method="post" action="<?= htmlspecialchars(app_url('/admin/addCompetence')) ?>" style="display:grid;gap:.7rem;">
                <div>
                    <label>Nom</label>
                    <input type="text" name="nom" maxlength="100" required>
                </div>
                <div>
                    <label>Description</label>
                    <textarea name="description" maxlength="500"></textarea>
                </div>
                <button class="btn primary" type="submit">Ajouter</button>
            </form>
        </div>

        <div class="card">
            <h3 style="margin-top:0;">Catalogue des competences</h3>
            <table>
                <thead>
                    <tr><th>ID</th><th>Nom</th><th>Description</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= (int)($row['id_competence'] ?? 0) ?></td>
                            <td>
                                <form method="post" action="<?= htmlspecialchars(app_url('/admin/updateCompetence')) ?>" style="display:grid;gap:.4rem;">
                                    <input type="hidden" name="id_competence" value="<?= (int)($row['id_competence'] ?? 0) ?>">
                                    <input type="text" name="nom" value="<?= htmlspecialchars((string)($row['nom_competence'] ?? '')) ?>" maxlength="100" required>
                            </td>
                            <td>
                                    <textarea name="description" maxlength="500"><?= htmlspecialchars((string)($row['description'] ?? '')) ?></textarea>
                            </td>
                            <td style="display:flex;gap:.4rem;align-items:flex-start;">
                                    <button class="btn" type="submit">Modifier</button>
                                </form>
                                <form method="post" action="<?= htmlspecialchars(app_url('/admin/deleteCompetence')) ?>" onsubmit="return confirm('Supprimer cette competence ?');">
                                    <input type="hidden" name="id_competence" value="<?= (int)($row['id_competence'] ?? 0) ?>">
                                    <button class="btn" type="submit">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4">Aucune competence.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
