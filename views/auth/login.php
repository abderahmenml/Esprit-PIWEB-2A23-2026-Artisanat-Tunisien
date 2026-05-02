<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CraftLink Tunisie - Connexion</title>
  <link href="public/css/style.css" rel="stylesheet">
</head>
<body>
<div class="left-panel">
  <div class="brand">
    <div class="logo-ring">
      <img src="public/assets/logo.png" alt="CraftLink Logo" class="logo-img">
    </div>
    <div class="brand-title">CraftLink Tunisie</div>
    <div class="brand-arabic">حرفة تونس</div>
    <div class="brand-sub">L'artisanat tunisien, a l'ere du numerique.</div>
  </div>
  <div class="divider-ornament"><div class="divider-diamond"></div></div>
  <div class="roles-title">Rejoignez notre ecosysteme</div>
  <div class="roles">
    <div class="role-card entrepreneur">Entrepreneur</div>
    <div class="role-card mentor">Mentor / Artisan</div>
    <div class="role-card investisseur">Investisseur</div>
  </div>
  <p class="left-quote">"Connecter les createurs, les artisans et les investisseurs."</p>
</div>

<div class="right-panel">
  <div class="form-header">
    <div class="form-label-small">Bienvenue</div>
    <div class="form-title">Connexion a votre espace</div>
    <div class="form-subtitle">Accedez a votre tableau de bord CraftLink</div>
  </div>

  <?php if (isset($_GET['success']) && $_GET['success'] === 'verified'): ?>
    <div class="alert-banner" style="display:block; background:#d4edda; color:#155724; border:1px solid #c3e6cb;">
      Utilisateur ajoute avec succes ! Vous pouvez maintenant vous connecter.
    </div>
  <?php endif; ?>

  <div class="role-tabs">
    <button type="button" class="role-tab active" onclick="setRole(this,'entrepreneur')"><span class="tab-icon">🎨</span> Entrepreneur</button>
    <button type="button" class="role-tab" onclick="setRole(this,'artisan')"><span class="tab-icon">🧑‍🎨</span> Artisan</button>
    <button type="button" class="role-tab" onclick="setRole(this,'mentor')"><span class="tab-icon">🎓</span> Mentor</button>
    <button type="button" class="role-tab" onclick="setRole(this,'investisseur')"><span class="tab-icon">💼</span> Investisseur</button>
  </div>

  <div class="alert-banner" id="alert-banner" style="display:none"></div>

  <div class="field-group">
    <label class="field-label" for="email">Adresse e-mail</label>
    <div class="field-wrap">
      <span class="field-icon">✉</span>
      <input type="text" id="email" placeholder="votre@email.com" autocomplete="email">
    </div>
    <div class="error-msg" id="email-err">Veuillez entrer une adresse e-mail valide.</div>
  </div>

  <div class="field-group">
    <label class="field-label" for="password">Mot de passe</label>
    <div class="field-wrap">
      <span class="field-icon">🔒</span>
      <input type="password" id="password" placeholder="********" autocomplete="current-password">
      <button type="button" class="toggle-pw" onclick="togglePw('password','pw-toggle')" id="pw-toggle">👁</button>
    </div>
    <div class="error-msg" id="pw-err">Le mot de passe est requis.</div>
  </div>

  <div class="field-group">
    <label class="field-label">Connexion par reconnaissance faciale</label>
    <div class="field-wrap" style="padding:1rem;display:block;">
      <p style="margin:0 0 .8rem;">Utilisez votre camera pour vous connecter avec votre visage.</p>
      <video id="rf-login-video" autoplay muted playsinline style="width:100%;max-height:220px;border-radius:12px;background:#000;display:none;"></video>
      <canvas id="rf-login-canvas" style="display:none;"></canvas>
      <input type="hidden" id="rf-login-descriptor">
      <input type="hidden" id="rf-login-face-id">
      <div id="rf-login-status" class="helper-text" style="margin-top:.7rem;">Camera non activee.</div>
      <div id="rf-login-face-id-label" class="helper-text" style="margin-top:.35rem;">ID visage : non detecte.</div>
      <div style="display:flex;gap:.7rem;flex-wrap:wrap;margin-top:.9rem;">
        <button type="button" class="btn-login" style="flex:1;min-width:180px;" onclick="startFaceLogin()">Activer camera</button>
      </div>
    </div>
  </div>

  <div class="options-row">
    <label class="checkbox-label">
      <input type="checkbox"> Se souvenir de moi
    </label>
    <a href="index.php?page=forgot_password" class="forgot-link">Mot de passe oublie ?</a>
  </div>

  <?php if (RecaptchaV2::isConfigured()): ?>
    <div class="recaptcha-wrap">
      <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars(RecaptchaV2::siteKey(), ENT_QUOTES, 'UTF-8') ?>"></div>
    </div>
  <?php endif; ?>

  <button class="btn-login" id="btn-login" onclick="handleLogin()">Se connecter -></button>

  <div class="or-divider"><span class="or-text">Nouveau sur CraftLink ?</span></div>
  <div class="register-link">Pas encore de compte ? <a href="index.php?page=register">Creez votre profil gratuitement</a></div>

  <div class="form-footer">
    <span class="footer-brand">Projet CraftLink · 2025-2026</span>
    <div class="footer-lang">
      <button type="button" class="lang-btn active">FR</button>
      <button type="button" class="lang-btn">AR</button>
      <button type="button" class="lang-btn">EN</button>
    </div>
  </div>
</div>

<script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<?php if (RecaptchaV2::isConfigured()): ?>
<script defer src="https://www.google.com/recaptcha/api.js"></script>
<?php endif; ?>
<script src="public/js/app.js?v=recaptcha-v2-2"></script>
</body>
</html>
