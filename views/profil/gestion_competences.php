<?php
$baseUrl = app_url();
$selectedCompetenceIds = array_map('intval', (array)($selectedCompetenceIds ?? []));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selection des competences</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(app_url('/public/css/style.css')) ?>">
    <style>
        body { padding: 1.2rem; }
        .card-box { max-width: 860px; margin: 0 auto; background: #fff; border: 1px solid #eadfcf; border-radius: 14px; padding: 1.1rem; }
        .muted-note { color: #7f6f5d; font-size: .9rem; margin-bottom: .8rem; }
        .grid-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: .65rem; margin: 1rem 0; }
        .item { border: 1px solid #e5d8c3; border-radius: 10px; padding: .65rem; background: #fdf9f3; }
        .item label { display: flex; align-items: flex-start; gap: .5rem; cursor: pointer; }
        .item .name { font-weight: 700; color: #4a2f1f; }
        .item .desc { color: #6e5d4c; font-size: .84rem; margin-top: .2rem; }
        .actions { display: flex; gap: .6rem; align-items: center; margin-top: .8rem; }
        .btn-main { border: 0; border-radius: 8px; padding: .55rem .9rem; background: #2e6b3e; color: #fff; cursor: pointer; font-weight: 700; }
        .btn-link { color: #8b5a3a; text-decoration: none; }
    </style>
</head>
<body>
<div class="card-box">
    <h1 style="margin-top:0;">Selection des competences</h1>
    <p class="muted-note">Les competences sont gerees par l'administrateur. Selectionnez uniquement celles de votre profil professionnel.</p>

    <form method="post" action="<?= htmlspecialchars(app_url('/profil/saveCompetences')) ?>">
        <div class="grid-list">
            <?php if (!empty($competenceCatalog)): ?>
                <?php foreach ($competenceCatalog as $catalog): ?>
                    <?php $idCompetence = (int)($catalog['id_competence'] ?? 0); ?>
                    <div class="item">
                        <label>
                            <input type="checkbox" name="competence_ids[]" value="<?= $idCompetence ?>" <?= in_array($idCompetence, $selectedCompetenceIds, true) ? 'checked' : '' ?>>
                            <span>
                                <span class="name"><?= htmlspecialchars((string)($catalog['nom_competence'] ?? '')) ?></span>
                                <?php if (!empty($catalog['description'])): ?>
                                    <span class="desc"><?= htmlspecialchars((string)$catalog['description']) ?></span>
                                <?php endif; ?>
                            </span>
                        </label>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucune competence disponible pour le moment.</p>
            <?php endif; ?>
        </div>

        <div class="actions">
            <button type="submit" class="btn-main">Enregistrer ma selection</button>
            <a class="btn-link" href="<?= htmlspecialchars(app_url('/profil')) ?>">Retour au profil</a>
        </div>
    </form>

    <hr style="margin:1rem 0;border:none;border-top:1px solid #eee0cb;">

    <h3 style="margin:.2rem 0 .5rem;">Competences actuellement associees</h3>
    <?php if (!empty($competences)): ?>
        <ul>
            <?php foreach ($competences as $c): ?>
                <li><strong><?= htmlspecialchars((string)($c['nom_competence'] ?? '')) ?></strong><?php if (!empty($c['description'])): ?> - <?= htmlspecialchars((string)$c['description']) ?><?php endif; ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="muted-note">Aucune competence associee a votre profil.</p>
    <?php endif; ?>
</div>
</body>
</html>