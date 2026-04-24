<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CraftLink Tunisie — Connexion</title>
  <link href="public/css/style.css" rel="stylesheet">
</head>
<body>

<!-- ── PANNEAU GAUCHE ── -->
<div class="left-panel">
  <div class="brand">
    <div class="logo-ring">
      <img src="public/assets/logo.png" alt="CraftLink Logo" class="logo-img">
    </div>
    <div class="brand-title">CraftLink Tunisie</div>
    <div class="brand-arabic">حرفة تونس</div>
    <div class="brand-sub">L'artisanat tunisien, à l'ère du numérique.</div>
  </div>
  <div class="divider-ornament"><div class="divider-diamond"></div></div>
  <div class="roles-title">Rejoignez notre écosystème</div>
  <div class="roles">
    <div class="role-card entrepreneur">🎨 Entrepreneur</div>
    <div class="role-card mentor">🧑‍🎨 Mentor / Artisan</div>
    <div class="role-card investisseur">💼 Investisseur</div>
  </div>
  <p class="left-quote">« Connecter les créateurs, les artisans et les investisseurs pour faire vivre l'artisanat tunisien. »</p>
</div>

<!-- ── PANNEAU DROIT ── -->
<div class="right-panel">
  <div class="form-header">
    <div class="form-label-small">Bienvenue</div>
    <div class="form-title">Connexion à votre espace</div>
    <div class="form-subtitle">Accédez à votre tableau de bord CraftLink</div>
  </div>

  <!-- Onglets rôles -->
  <div class="role-tabs">
    <button type="button" class="role-tab active" onclick="setRole(this,'entrepreneur')"><span class="tab-icon">🎨</span> Entrepreneur</button>
    <button type="button" class="role-tab" onclick="setRole(this,'artisan')"><span class="tab-icon">🧑‍🎨</span> Artisan</button>
    <button type="button" class="role-tab" onclick="setRole(this,'mentor')"><span class="tab-icon">🎓</span> Mentor</button>
    <button type="button" class="role-tab" onclick="setRole(this,'investisseur')"><span class="tab-icon">💼</span> Investisseur</button>
  </div>

  <div class="alert-banner" id="alert-banner" style="display:none"></div>

  <!-- Email — pas de type="email", validation JS uniquement -->
  <div class="field-group">
    <label class="field-label" for="email">Adresse e-mail</label>
    <div class="field-wrap">
      <span class="field-icon">✉</span>
      <input type="text" id="email" placeholder="votre@email.com" autocomplete="email">
    </div>
    <div class="error-msg" id="email-err">Veuillez entrer une adresse e-mail valide.</div>
  </div>

  <!-- Mot de passe — pas de required -->
  <div class="field-group">
    <label class="field-label" for="password">Mot de passe</label>
    <div class="field-wrap">
      <span class="field-icon">🔒</span>
      <input type="password" id="password" placeholder="••••••••" autocomplete="current-password">
      <button type="button" class="toggle-pw" onclick="togglePw('password','pw-toggle')" id="pw-toggle">👁</button>
    </div>
    <div class="error-msg" id="pw-err">Le mot de passe est requis.</div>
  </div>

  <div class="options-row">
    <label class="checkbox-label">
      <input type="checkbox"> Se souvenir de moi
    </label>
    <a href="index.php?page=forgot_password" class="forgot-link">Mot de passe oublié ?</a>
  </div>

  <button class="btn-login" id="btn-login" onclick="handleLogin()">Se connecter →</button>

  <div class="or-divider"><span class="or-text">Nouveau sur CraftLink ?</span></div>
  <div class="register-link">Pas encore de compte ? <a href="index.php?page=register">Créez votre profil gratuitement</a></div>

  <div class="form-footer">
    <span class="footer-brand">Projet CraftLink · 2025–2026</span>
    <div class="footer-lang">
      <button type="button" class="lang-btn active">FR</button>
      <button type="button" class="lang-btn">AR</button>
      <button type="button" class="lang-btn">EN</button>
    </div>
  </div>
</div>

<script src="public/js/app.js"></script>
</body>
</html>
