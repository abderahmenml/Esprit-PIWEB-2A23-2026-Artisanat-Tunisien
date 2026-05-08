<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CraftLink Tunisie — Inscription</title>
  <link href="assets/css/login.css" rel="stylesheet">
</head>
<body>

<div class="left-panel">
  <div class="brand">
    <div class="logo-ring">
      <img src="assets/images/logo.png" alt="CraftLink Logo" class="logo-img">
    </div>
    <div class="brand-title">CraftLink Tunisie</div>
    <div class="brand-arabic">حرفة تونس</div>
    <div class="brand-sub">L'artisanat tunisien, a l'ere du numerique.</div>
  </div>
  <div class="divider-ornament"><div class="divider-diamond"></div></div>
  <div class="roles-title">Rejoignez notre ecosysteme</div>
  <div class="roles">
    <div class="role-card entrepreneur">🎨 Entrepreneur</div>
    <div class="role-card mentor">🧑‍🎨 Mentor / Artisan</div>
    <div class="role-card investisseur">💼 Investisseur</div>
  </div>
  <p class="left-quote">« Connecter les createurs, les artisans et les investisseurs pour faire vivre l'artisanat tunisien. »</p>
</div>

<div class="right-panel register-panel">
  <div class="form-header">
    <div class="form-label-small">Nouveau compte</div>
    <div class="form-title">Creez votre profil</div>
    <div class="form-subtitle">Rejoignez la communaute CraftLink gratuitement</div>
  </div>

  <div class="role-tabs">
    <button type="button" class="role-tab active" onclick="setRole(this,'entrepreneur')"><span class="tab-icon">🎨</span> Entrepreneur</button>
    <button type="button" class="role-tab" onclick="setRole(this,'artisan')"><span class="tab-icon">🧑‍🎨</span> Artisan</button>
    <button type="button" class="role-tab" onclick="setRole(this,'mentor')"><span class="tab-icon">🎓</span> Mentor</button>
    <button type="button" class="role-tab" onclick="setRole(this,'investisseur')"><span class="tab-icon">💼</span> Investisseur</button>
  </div>

  <div class="alert-banner" id="alert-banner" style="display:none"></div>

  <div class="fields-row">
    <div class="field-group" style="flex:1">
      <label class="field-label" for="nom">Nom</label>
      <div class="field-wrap">
        <span class="field-icon">👤</span>
        <input type="text" id="nom" placeholder="Votre nom" autocomplete="family-name">
      </div>
      <div class="error-msg" id="nom-err">Le nom est requis (lettres uniquement).</div>
    </div>
    <div class="field-group" style="flex:1">
      <label class="field-label" for="prenom">Prenom</label>
      <div class="field-wrap">
        <span class="field-icon">👤</span>
        <input type="text" id="prenom" placeholder="Votre prenom" autocomplete="given-name">
      </div>
      <div class="error-msg" id="prenom-err">Le prenom est requis (lettres uniquement).</div>
    </div>
  </div>

  <div class="field-group">
    <label class="field-label" for="email">Adresse e-mail</label>
    <div class="field-wrap">
      <span class="field-icon">✉</span>
      <input type="text" id="email" placeholder="votre@email.com" autocomplete="email">
    </div>
    <div class="error-msg" id="email-err">Veuillez entrer une adresse e-mail valide.</div>
  </div>

  <div class="fields-row">
    <div class="field-group" style="flex:1">
      <label class="field-label" for="password">Mot de passe</label>
      <div class="field-wrap">
        <span class="field-icon">🔒</span>
        <input type="password" id="password" placeholder="••••••••" autocomplete="new-password">
        <button type="button" class="toggle-pw" onclick="togglePw('password','pw-toggle')" id="pw-toggle">👁</button>
      </div>
      <div class="error-msg" id="pw-err">Minimum 6 caracteres.</div>
    </div>
    <div class="field-group" style="flex:1">
      <label class="field-label" for="confirm">Confirmer</label>
      <div class="field-wrap">
        <span class="field-icon">🔒</span>
        <input type="password" id="confirm" placeholder="••••••••" autocomplete="new-password">
        <button type="button" class="toggle-pw" onclick="togglePw('confirm','conf-toggle')" id="conf-toggle">👁</button>
      </div>
      <div class="error-msg" id="conf-err">Les mots de passe ne correspondent pas.</div>
    </div>
  </div>

  <div class="pw-strength-wrap">
    <div class="pw-strength-bar"><div class="pw-strength-fill" id="pw-strength-fill"></div></div>
    <span class="pw-strength-label" id="pw-strength-label">Force du mot de passe</span>
  </div>

  <div class="field-group">
    <label class="field-label">Authentification intelligente RF</label>
    <div class="field-wrap" style="padding:1rem;display:block;">
      <p style="margin:0 0 .8rem;">Capturez votre visage (optionnel) pour activer la connexion par reconnaissance faciale.</p>
      <video id="rf-register-video" autoplay muted playsinline style="width:100%;max-height:220px;border-radius:12px;background:#000;display:none;"></video>
      <canvas id="rf-register-canvas" style="display:none;"></canvas>
      <input type="hidden" id="rf-register-descriptor">
      <input type="hidden" id="rf-register-face-id">
      <div id="rf-register-status" class="helper-text" style="margin-top:.7rem;">Aucune capture faciale.</div>
      <div id="rf-register-face-id-label" class="helper-text" style="margin-top:.35rem;">ID visage : non detecte.</div>
      <div style="display:flex;gap:.7rem;flex-wrap:wrap;margin-top:.9rem;">
        <button type="button" class="btn-login" style="flex:1;min-width:180px;" onclick="startFaceEnrollment()">Activer reconnaissance faciale</button>
        <button type="button" class="btn-login" style="flex:1;min-width:180px;" onclick="captureFaceEnrollment()">Capturer mon visage</button>
      </div>
    </div>
  </div>

  <div class="options-row" style="margin-bottom:1rem">
    <label class="checkbox-label">
      <input type="checkbox" id="terms">
      J'accepte les <a href="#" class="forgot-link" onclick="openTermsModal(event)">conditions d'utilisation</a>
    </label>
  </div>
  <div class="error-msg" id="terms-err" style="margin-top:-.6rem;margin-bottom:.8rem">Veuillez accepter les conditions.</div>

  <?php if (RecaptchaV2::isConfigured()): ?>
    <div class="recaptcha-wrap">
      <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars(RecaptchaV2::siteKey(), ENT_QUOTES, 'UTF-8') ?>"></div>
    </div>
  <?php endif; ?>

  <button type="button" class="btn-login" id="btn-register" onclick="handleRegister()">Creer mon profil -></button>

  <div class="or-divider"><span class="or-text">Deja inscrit ?</span></div>
  <div class="register-link">Vous avez deja un compte ? <a href="login.php">Connectez-vous ici</a></div>

  <div class="terms-modal" id="terms-modal" style="display:none;">
    <div class="terms-backdrop" onclick="closeTermsModal()"></div>
    <div class="terms-box">
      <button type="button" class="terms-close" onclick="closeTermsModal()">×</button>
      <h2>Conditions d'utilisation</h2>
      <div class="terms-content">
        <p><strong>1. Objet de la plateforme</strong><br>CraftLink Tunisie est une plateforme dediee a la mise en relation entre entrepreneurs, artisans, mentors et investisseurs autour de l'artisanat tunisien.</p>
        <p><strong>2. Creation de compte</strong><br>L'utilisateur s'engage a fournir des informations exactes, completes et a jour lors de son inscription.</p>
        <p><strong>3. Securite du compte</strong><br>Chaque utilisateur est responsable de la confidentialite de son mot de passe et des activites effectuees depuis son compte.</p>
        <p><strong>4. Utilisation acceptable</strong><br>Il est interdit de publier de fausses informations, des contenus offensants, frauduleux ou contraires aux lois en vigueur.</p>
        <p><strong>5. Respect de la communaute</strong><br>Les echanges entre membres doivent rester professionnels, respectueux et conformes aux valeurs de la plateforme.</p>
        <p><strong>6. Donnees personnelles</strong><br>Les donnees collectees sont utilisees uniquement dans le cadre du fonctionnement de la plateforme.</p>
      </div>
      <div class="terms-actions">
        <button type="button" class="btn-terms-accept" onclick="acceptTermsFromModal()">J'accepte</button>
      </div>
    </div>
  </div>

  <div class="form-footer">
    <span class="footer-brand">Projet CraftLink · 2025–2026</span>
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
<script src="assets/js/login.js?v=2"></script>
</body>
</html>
