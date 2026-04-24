<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CraftLink - Mon espace</title>
  <link href="public/css/style.css" rel="stylesheet">
  <style>
    body { display:flex; align-items:center; justify-content:center; background:#F5ECD7; min-height:100vh; overflow:auto; }
    .dash-card {
      background:white; border-radius:20px; padding:3rem 3.5rem;
      box-shadow:0 8px 40px rgba(59,35,20,.12);
      text-align:center; max-width:560px; width:100%;
    }
    .dash-title { font-family:Georgia,serif; font-size:1.8rem; color:var(--brun); margin-bottom:.5rem; }
    .dash-sub { font-size:.95rem; color:var(--text-muted); margin-bottom:2rem; line-height:1.6; }
    .info-row {
      display:flex; align-items:center; justify-content:space-between;
      padding:.75rem 1rem; border-radius:8px;
      background:rgba(196,154,108,.1); margin-bottom:.6rem; font-size:.88rem;
    }
    .info-label { color:var(--text-muted); font-weight:600; }
    .info-value { color:var(--brun); font-weight:bold; }
    .role-badge { display:inline-block; padding:.3rem .8rem; border-radius:20px; font-size:.8rem; font-weight:bold; }
    .role-entrepreneur { background:var(--marron); color:white; }
    .role-artisan { background:#7B5EA7; color:white; }
    .role-mentor { background:var(--vert); color:white; }
    .role-investisseur { background:#C49A6C; color:white; }
    .btn-row { display:flex; gap:.8rem; margin-top:2rem; justify-content:center; flex-wrap:wrap; }
    .btn-dash {
      padding:.7rem 1.5rem; border:none; border-radius:8px;
      font-family:Georgia,serif; font-size:.9rem; font-weight:bold;
      cursor:pointer; transition:all .2s; text-decoration:none; display:inline-block;
    }
    .btn-logout { background:var(--brun); color:var(--creme); }
    .btn-profile { background:var(--vert); color:white; }
    .btn-admin { background:#5b3b1f; color:white; }
    .craftlink-logo { font-family:Georgia,serif; font-size:.9rem; color:var(--caramel); margin-top:2rem; font-style:italic; }
  </style>
</head>
<body>
<div class="dash-card">
  <div style="font-size:3.5rem; margin-bottom:1rem;">✅</div>
  <div class="dash-title">Bienvenue, <?= htmlspecialchars($_SESSION['prenom']) ?> !</div>
  <div class="dash-sub">Vous etes connecte a votre espace CraftLink Tunisie.</div>

  <div class="info-row">
    <span class="info-label">Nom complet</span>
    <span class="info-value"><?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?></span>
  </div>
  <div class="info-row">
    <span class="info-label">Email</span>
    <span class="info-value"><?= htmlspecialchars($_SESSION['email']) ?></span>
  </div>
  <div class="info-row">
    <span class="info-label">Role</span>
    <span class="info-value">
      <?php
      $roleLabels = [
          'entrepreneur' => 'Entrepreneur',
          'artisan' => 'Artisan',
          'mentor' => 'Mentor',
          'investisseur' => 'Investisseur',
          'admin' => 'Admin',
      ];
      $role = $_SESSION['role'];
      ?>
      <span class="role-badge role-<?= htmlspecialchars($role) ?>"><?= htmlspecialchars($roleLabels[$role] ?? ucfirst($role)) ?></span>
    </span>
  </div>

  <div class="btn-row">
    <?php if ($role === 'admin'): ?>
      <a href="<?= APP_URL ?>/admin/index.php" class="btn-dash btn-admin">BackOffice Admin</a>
    <?php endif; ?>
    <a href="<?= APP_URL ?>/index.php?page=profile" class="btn-dash btn-profile">Mon profil</a>
    <a href="<?= APP_URL ?>/index.php?page=logout" class="btn-dash btn-logout">Se deconnecter</a>
  </div>

  <div class="craftlink-logo">CraftLink Tunisie</div>
</div>
</body>
</html>
