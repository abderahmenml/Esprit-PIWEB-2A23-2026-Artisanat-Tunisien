<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Donnees - CraftLink</title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/style.css">
    <style>
        body { background:#f7f1e6; margin:0; }
        .settings-container { max-width:900px; margin:40px auto; padding:20px; }
        .settings-header { background:linear-gradient(135deg, #2E6B3E 0%, #C49A6C 100%); color:white; padding:28px; border-radius:18px; margin-bottom:24px; }
        .settings-card { background:white; border-radius:16px; padding:24px; box-shadow:0 8px 26px rgba(59,35,20,.08); margin-bottom:18px; }
        .setting-row { display:flex; justify-content:space-between; gap:20px; padding:14px 0; border-bottom:1px solid #f0e6d8; }
        .setting-row:last-child { border-bottom:none; }
        .btn { display:inline-block; padding:12px 18px; border-radius:10px; text-decoration:none; font-weight:bold; }
        .btn-primary { background:#2E6B3E; color:white; }
        .btn-secondary { background:#efe4d0; color:#5b3b1f; }
        .btn-danger { background:#b84040; color:white; }
    </style>
</head>
<body>
    <?php
    $statuts = [
        'celibataire' => 'Celibataire',
        'marie' => 'Marie(e)',
        'divorce' => 'Divorce(e)',
        'veuf' => 'Veuf(ve)',
        'autre' => 'Autre',
    ];
    ?>
    <div class="settings-container">
        <div class="settings-header">
            <h1 style="margin:0;">Mes donnees</h1>
            <p style="margin:8px 0 0;">Consultez les informations enregistrees sur votre compte.</p>
        </div>

        <div class="settings-card">
            <div class="setting-row"><strong>Nom complet</strong><span><?php echo htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')); ?></span></div>
            <div class="setting-row"><strong>Email</strong><span><?php echo htmlspecialchars($user['email']); ?></span></div>
            <div class="setting-row"><strong>Role</strong><span><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span></div>
            <div class="setting-row"><strong>Date de naissance</strong><span><?php echo !empty($user['date_naissance']) ? htmlspecialchars(date('d/m/Y', strtotime($user['date_naissance']))) : 'Non renseignee'; ?></span></div>
            <div class="setting-row"><strong>Statut marital</strong><span><?php echo !empty($user['statut_marital']) ? htmlspecialchars($statuts[$user['statut_marital']] ?? $user['statut_marital']) : 'Non renseigne'; ?></span></div>
            <div class="setting-row"><strong>Etat du compte</strong><span><?php echo htmlspecialchars(ucfirst($user['etat_compte'])); ?></span></div>
        </div>

        <div class="settings-card">
            <a href="index.php?page=profile&method=edit" class="btn btn-primary">Modifier mes donnees</a>
            <a href="index.php?page=profile" class="btn btn-secondary">Retour au profil</a>
            <a href="index.php?page=profile&method=deleteForm" class="btn btn-danger">Supprimer mon compte</a>
        </div>
    </div>
</body>
</html>
