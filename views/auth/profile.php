<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - CraftLink</title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/style.css">
    <style>
        body { background:#f7f1e6; margin:0; font-family:Georgia, serif; }
        .profile-container { max-width:1000px; margin:40px auto; padding:20px; }
        .profile-header {
            background:linear-gradient(135deg, #2E6B3E 0%, #C49A6C 100%);
            color:white; border-radius:18px; padding:28px; display:flex; gap:24px; align-items:center;
            box-shadow:0 12px 40px rgba(59,35,20,.12);
        }
        .avatar {
            width:120px; height:120px; border-radius:50%; overflow:hidden; flex:0 0 120px;
            background:rgba(255,255,255,.18); display:flex; align-items:center; justify-content:center;
            font-size:42px; font-weight:bold; border:3px solid rgba(255,255,255,.45);
        }
        .avatar img { width:100%; height:100%; object-fit:cover; }
        .profile-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:20px; margin-top:24px; }
        .profile-card { background:white; border-radius:16px; padding:22px; box-shadow:0 8px 26px rgba(59,35,20,.08); }
        .profile-card h3 { margin-top:0; color:#5b3b1f; }
        .profile-field { margin-bottom:16px; }
        .profile-field label { display:block; font-size:12px; text-transform:uppercase; color:#8c6a49; margin-bottom:6px; font-weight:bold; }
        .profile-value { color:#2d251e; font-size:16px; }
        .empty-state { color:#8f8f8f; font-style:italic; }
        .action-buttons { display:flex; gap:12px; flex-wrap:wrap; margin-top:20px; }
        .btn { display:inline-block; padding:12px 18px; border-radius:10px; text-decoration:none; font-weight:bold; }
        .btn-primary { background:#2E6B3E; color:white; }
        .btn-secondary { background:#efe4d0; color:#5b3b1f; }
        .btn-danger { background:#b84040; color:white; }
        .flash { padding:14px 16px; border-radius:12px; margin:20px 0; }
        .flash-success { background:#dff2e3; color:#205b2e; }
        .status-pill { display:inline-block; padding:6px 10px; border-radius:999px; background:#f0e5d2; color:#5b3b1f; font-size:13px; }
    </style>
</head>
<body>
    <?php
    $imageUrl = !empty($user['image_profil']) ? APP_URL . '/' . ltrim($user['image_profil'], '/') : null;
    $fullName = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
    $initials = strtoupper(substr((string) ($user['prenom'] ?? 'U'), 0, 1) . substr((string) ($user['nom'] ?? ''), 0, 1));
    $statuts = [
        'celibataire' => 'Celibataire',
        'marie' => 'Marie(e)',
        'divorce' => 'Divorce(e)',
        'veuf' => 'Veuf(ve)',
        'autre' => 'Autre',
    ];
    ?>
    <div class="profile-container">
        <div class="profile-header">
            <div class="avatar">
                <?php if ($imageUrl): ?>
                    <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="Photo de profil">
                <?php else: ?>
                    <?php echo htmlspecialchars($initials ?: 'U'); ?>
                <?php endif; ?>
            </div>
            <div>
                <h1 style="margin:0 0 8px;"><?php echo htmlspecialchars($fullName); ?></h1>
                <div style="opacity:.92; margin-bottom:10px;"><?php echo htmlspecialchars($user['email']); ?></div>
                <span class="status-pill"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
            </div>
        </div>

        <?php if (isset($_SESSION['flash'])): ?>
            <div class="flash flash-<?php echo $_SESSION['flash']['type'] === 'success' ? 'success' : 'danger'; ?>">
                <?php echo htmlspecialchars($_SESSION['flash']['msg']); ?>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <div class="profile-grid">
            <div class="profile-card">
                <h3>Informations personnelles</h3>
                <div class="profile-field">
                    <label>Prenom</label>
                    <div class="profile-value"><?php echo htmlspecialchars($user['prenom']); ?></div>
                </div>
                <div class="profile-field">
                    <label>Nom</label>
                    <div class="profile-value"><?php echo htmlspecialchars($user['nom']); ?></div>
                </div>
                <div class="profile-field">
                    <label>Email</label>
                    <div class="profile-value"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                <div class="profile-field">
                    <label>Date d'inscription</label>
                    <div class="profile-value"><?php echo htmlspecialchars(date('d/m/Y', strtotime($user['date_creation']))); ?></div>
                </div>
            </div>

            <div class="profile-card">
                <h3>Informations du profil</h3>
                <div class="profile-field">
                    <label>Date de naissance</label>
                    <div class="profile-value">
                        <?php echo !empty($user['date_naissance']) ? htmlspecialchars(date('d/m/Y', strtotime($user['date_naissance']))) : '<span class="empty-state">Non renseignee</span>'; ?>
                    </div>
                </div>
                <div class="profile-field">
                    <label>Statut marital</label>
                    <div class="profile-value">
                        <?php echo !empty($user['statut_marital']) ? htmlspecialchars($statuts[$user['statut_marital']] ?? $user['statut_marital']) : '<span class="empty-state">Non renseigne</span>'; ?>
                    </div>
                </div>
                <div class="profile-field">
                    <label>Etat du compte</label>
                    <div class="profile-value"><?php echo htmlspecialchars(ucfirst($user['etat_compte'])); ?></div>
                </div>
            </div>
        </div>

        <div class="profile-card" style="margin-top:20px;">
            <h3>Actions</h3>
            <div class="action-buttons">
                <a href="index.php?page=profile&method=edit" class="btn btn-primary">Modifier mon profil</a>
                <a href="index.php?page=profile&method=settings" class="btn btn-secondary">Voir mes donnees</a>
                <a href="index.php?page=profile&method=deleteForm" class="btn btn-danger">Supprimer mon compte</a>
                <a href="index.php?page=dashboard" class="btn btn-secondary">Retour a mon espace</a>
            </div>
        </div>
    </div>
</body>
</html>
