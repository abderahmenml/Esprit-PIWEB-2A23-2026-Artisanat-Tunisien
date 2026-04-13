<?php
// profil_public.php
require_once 'config.php';
require_once 'functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: annuaire.php"); exit; }

$pdo = getPDO();
$user = getUserById($id);
if (!$user) { header("Location: annuaire.php"); exit; }

$competences = getUserCompetences($id);
$experiences = getUserExperiences($id);
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($user['prenom'].' '.$user['nom']) ?> — حرفة Tunisie</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>...</header>
<div class="page" style="grid-template-columns: 320px 1fr;">
    <!-- Sidebar avec les infos -->
    <aside class="sidebar">
        <div class="card profile-card">
            <div class="profile-top">
                <div class="avatar-wrap">
                    <div class="avatar"><?= strtoupper(substr($user['prenom'],0,1).substr($user['nom'],0,1)) ?></div>
                </div>
                <div class="profile-name"><?= htmlspecialchars($user['prenom'].' '.$user['nom']) ?></div>
                <div class="profile-title"><?= htmlspecialchars($user['specialite'] ?? 'Artisan') ?></div>
                <div class="profile-location">📍 <?= htmlspecialchars($user['ville'] ?? 'Tunisie') ?></div>
                <?php if ($user['portfolio']): ?>
                    <a href="<?= htmlspecialchars($user['portfolio']) ?>" target="_blank" class="edit-btn" style="text-align:center;">🌐 Portfolio</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card">
            <div class="section-title">Contact</div>
            <div class="contact-row">📧 <?= htmlspecialchars($user['email']) ?></div>
            <div class="contact-row">📞 <?= htmlspecialchars($user['telephone'] ?? 'Non renseigné') ?></div>
        </div>
        <div class="card">
            <div class="section-title">Compétences</div>
            <?php foreach ($competences as $c): ?>
                <div class="skill-item">
                    <div class="skill-name"><?= htmlspecialchars($c['nom_competence']) ?></div>
                    <div class="skill-bar"><div class="skill-fill" style="width:<?= $c['niveau'] ?? 50 ?>%"></div></div>
                </div>
            <?php endforeach; ?>
        </div>
    </aside>
    <!-- Main content -->
    <main class="main-col">
        <div class="card">
            <div class="section-title">À propos</div>
            <p class="bio-text"><?= nl2br(htmlspecialchars($user['bio'] ?? 'Aucune biographie.')) ?></p>
        </div>
        <div class="card">
            <div class="section-title">Expériences</div>
            <?php foreach ($experiences as $exp): ?>
                <div class="exp-item">
                    <div class="exp-dot">💼</div>
                    <div>
                        <div class="exp-role"><?= htmlspecialchars($exp['poste'] ?? 'Expérience') ?></div>
                        <div class="exp-company"><?= htmlspecialchars($exp['entreprise'] ?? '') ?></div>
                        <div class="exp-period"><?= date('Y', strtotime($exp['date_debut'])) ?> – <?= $exp['date_fin'] ? date('Y', strtotime($exp['date_fin'])) : 'Présent' ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
</body>
</html>