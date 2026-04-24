<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CraftLink Tunisie — Inscription</title>
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

<div class="right-panel register-panel">
  <div class="form-header">
    <div class="form-label-small">Nouveau compte</div>
    <div class="form-title">Créez votre profil</div>
    <div class="form-subtitle">Rejoignez la communauté CraftLink gratuitement</div>
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
        <!-- type="text" seulement, pas de required ni pattern -->
        <input type="text" id="nom" placeholder="Votre nom" autocomplete="family-name">
      </div>
      <div class="error-msg" id="nom-err">Le nom est requis (lettres uniquement).</div>
    </div>
    <div class="field-group" style="flex:1">
      <label class="field-label" for="prenom">Prénom</label>
      <div class="field-wrap">
        <span class="field-icon">👤</span>
        <input type="text" id="prenom" placeholder="Votre prénom" autocomplete="given-name">
      </div>
      <div class="error-msg" id="prenom-err">Le prénom est requis (lettres uniquement).</div>
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
      <div class="error-msg" id="pw-err">Minimum 6 caractères.</div>
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

  <div class="options-row" style="margin-bottom:1rem">
    <label class="checkbox-label">
      <input type="checkbox" id="terms">
      J'accepte les <a href="#" class="forgot-link" onclick="openTermsModal(event)">conditions d'utilisation</a>
    </label>
  </div>
  <div class="error-msg" id="terms-err" style="margin-top:-.6rem;margin-bottom:.8rem">Veuillez accepter les conditions.</div>

  <button type="button" class="btn-login" id="btn-register" onclick="handleRegister()">Créer mon profil →</button>

  <div class="or-divider"><span class="or-text">Déjà inscrit ?</span></div>
  <div class="register-link">Vous avez déjà un compte ? <a href="index.php?page=login">Connectez-vous ici</a></div>

  <!-- Modal CGU -->
  <div class="terms-modal" id="terms-modal" style="display:none;">
    <div class="terms-backdrop" onclick="closeTermsModal()"></div>
    <div class="terms-box">
      <button type="button" class="terms-close" onclick="closeTermsModal()">×</button>
      <h2>Conditions d'utilisation</h2>
      <div class="terms-content">
        <p><strong>1. Objet de la plateforme</strong><br>CraftLink Tunisie est une plateforme dédiée à la mise en relation entre entrepreneurs, artisans, mentors et investisseurs autour de l'artisanat tunisien.</p>
        <p><strong>2. Création de compte</strong><br>L'utilisateur s'engage à fournir des informations exactes, complètes et à jour lors de son inscription.</p>
        <p><strong>3. Sécurité du compte</strong><br>Chaque utilisateur est responsable de la confidentialité de son mot de passe et des activités effectuées depuis son compte.</p>
        <p><strong>4. Utilisation acceptable</strong><br>Il est interdit de publier de fausses informations, des contenus offensants, frauduleux ou contraires aux lois en vigueur.</p>
        <p><strong>5. Respect de la communauté</strong><br>Les échanges entre membres doivent rester professionnels, respectueux et conformes aux valeurs de la plateforme.</p>
        <p><strong>6. Données personnelles</strong><br>Les données collectées sont utilisées uniquement dans le cadre du fonctionnement de la plateforme.</p>
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

<script src="public/js/app.js"></script>
</body>
</html>
