<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CraftLink — Mon espace</title>
  <link href="public/css/style.css" rel="stylesheet">
  <style>
    body { display:flex; align-items:center; justify-content:center; background:#F5ECD7; min-height:100vh; overflow:auto; }
    .dash-card {
      background:white; border-radius:20px; padding:3rem 3.5rem;
      box-shadow:0 8px 40px rgba(59,35,20,.12);
      text-align:center; max-width:520px; width:100%;
      animation:popIn .5s ease both;
    }
    .success-icon { font-size:3.5rem; margin-bottom:1rem; }
    .dash-title   { font-family:Georgia,serif; font-size:1.8rem; color:var(--brun); margin-bottom:.5rem; }
    .dash-sub     { font-size:.9rem; color:var(--text-muted); margin-bottom:2rem; line-height:1.6; }
    .info-row {
      display:flex; align-items:center; justify-content:space-between;
      padding:.75rem 1rem; border-radius:8px;
      background:rgba(196,154,108,.1); margin-bottom:.6rem; font-size:.88rem;
    }
    .info-label { color:var(--text-muted); font-weight:600; }
    .info-value { color:var(--brun); font-weight:bold; }
    .role-badge { display:inline-block; padding:.3rem .8rem; border-radius:20px; font-size:.8rem; font-weight:bold; }
    .role-entrepreneur { background:var(--marron); color:white; }
    .role-artisan      { background:#7B5EA7;       color:white; }
    .role-mentor       { background:var(--vert);   color:white; }
    .role-investisseur { background:#C49A6C;       color:white; }
    .role-admin        { background:var(--brun);   color:white; }
    .btn-row { display:flex; gap:.8rem; margin-top:2rem; justify-content:center; flex-wrap:wrap; }
    .btn-dash {
      padding:.7rem 1.5rem; border:none; border-radius:8px;
      font-family:Georgia,serif; font-size:.9rem; font-weight:bold;
      cursor:pointer; transition:all .2s; text-decoration:none; display:inline-block;
    }
    .btn-logout  { background:var(--brun);  color:var(--creme); }
    .btn-logout:hover { background:var(--brun-mid); transform:translateY(-1px); }
    .btn-admin   { background:var(--vert);  color:white; }
    .btn-admin:hover { background:#245c32; transform:translateY(-1px); }
    .craftlink-logo { font-family:Georgia,serif; font-size:.9rem; color:var(--caramel); margin-top:2rem; font-style:italic; }
    @keyframes popIn { from{opacity:0;transform:scale(.92) translateY(20px)} to{opacity:1;transform:scale(1) translateY(0)} }
  </style>
</head>
<body>
<div class="dash-card">
  <div class="success-icon">✅</div>
  <div class="dash-title">Bienvenue, <?= htmlspecialchars($_SESSION['prenom']) ?> !</div>
  <div class="dash-sub">Vous êtes connecté à votre espace CraftLink Tunisie.<br>Votre session est active.</div>

  <div class="info-row">
    <span class="info-label">👤 Nom complet</span>
    <span class="info-value"><?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?></span>
  </div>
  <div class="info-row">
    <span class="info-label">✉ Email</span>
    <span class="info-value"><?= htmlspecialchars($_SESSION['email']) ?></span>
  </div>
  <div class="info-row">
    <span class="info-label">🎭 Rôle</span>
    <span class="info-value">
      <?php
      $roleLabels = ['entrepreneur'=>'🎨 Entrepreneur','artisan'=>'🧑‍🎨 Artisan','mentor'=>'🎓 Mentor','investisseur'=>'💼 Investisseur','admin'=>'🛡️ Admin'];
      $role = $_SESSION['role'];
      ?>
      <span class="role-badge role-<?= $role ?>"><?= $roleLabels[$role] ?? ucfirst($role) ?></span>
    </span>
  </div>

  <div class="btn-row">
    <?php if ($role === 'admin'): ?>
    <a href="admin/index.php" class="btn-dash btn-admin">🛡️ BackOffice Admin</a>
    <?php endif; ?>
    <a href="index.php?page=logout" class="btn-dash btn-logout">🚪 Se déconnecter</a>
  </div>

  <div class="craftlink-logo">CraftLink Tunisie · حرفة تونس</div>
</div>
</body>
</html>
