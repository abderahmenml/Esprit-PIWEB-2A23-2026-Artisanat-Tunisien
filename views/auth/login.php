<?php
// views/auth/login.php
// Variables: $erreur, $erreur_insc
$baseUrl = app_url();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CraftLink Tunisie — Connexion</title>
<link rel="stylesheet" href="<?= htmlspecialchars(app_url('/public/css/style.css')) ?>">
</head>
<body class="login-page">
<!-- ── LEFT ── -->
<div class="left-panel">
  <div class="brand">
    <div class="logo-ring">
      <span style="color:#f5ecd7;font-size:1.6rem;">ح</span>
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
  <p class="left-quote">
    « Connecter les créateurs, les artisans et les investisseurs pour faire vivre l'artisanat tunisien. »
  </p>
</div>
<!-- ── RIGHT ── -->
<div class="right-panel">
  <div class="form-header">
    <div class="form-label-small">Bienvenue</div>
    <div class="form-title">Connexion à votre espace</div>
    <div class="form-subtitle">Accédez à votre tableau de bord CraftLink</div>
  </div>
  <!-- Role tabs -->
  <div class="role-tabs">
    <button class="role-tab active" type="button" onclick="setRole(this,'entrepreneur')">
      <span class="tab-icon">🎨</span> Entrepreneur
    </button>
    <button class="role-tab" type="button" onclick="setRole(this,'artisan')">
      <span class="tab-icon">🧑‍🎨</span> Artisan
    </button>
    <button class="role-tab" type="button" onclick="setRole(this,'mentor')">
      <span class="tab-icon">🎓</span> Mentor
    </button>
    <button class="role-tab" type="button" onclick="setRole(this,'investisseur')">
      <span class="tab-icon">💼</span> Investisseur
    </button>
  </div>
  <form method="POST" action="" autocomplete="on" class="login-form">
    <!-- Email -->
    <div class="field-group">
      <label class="field-label" for="email">Adresse e-mail</label>
      <div class="field-wrap">
        <span class="field-icon">✉</span>
        <input type="email" id="email" name="email" placeholder="votre@email.com" autocomplete="email" maxlength="120" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
      </div>
      <div class="error-msg" id="email-err" style="display:none;">Veuillez entrer une adresse e-mail valide.</div>
    </div>
    <!-- Password -->
    <div class="field-group">
      <label class="field-label" for="password">Mot de passe</label>
      <div class="field-wrap">
        <span class="field-icon">🔒</span>
        <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" minlength="8" maxlength="72" required>
        <button class="toggle-pw" type="button" onclick="togglePw()" id="pw-toggle" title="Afficher / masquer">👁</button>
      </div>
      <div class="error-msg" id="pw-err" style="display:none;">Le mot de passe est requis.</div>
    </div>
    <!-- PHP Error -->
    <?php if (!empty($erreur)): ?>
      <div class="error-msg" style="display:block; color:#b00; margin-bottom:10px; text-align:center;">
        <?php echo $erreur; ?>
      </div>
    <?php endif; ?>
    <!-- Remember / Forgot -->
    <div class="options-row">
      <label class="checkbox-label">
        <input type="checkbox" name="remember"> Se souvenir de moi
      </label>
      <a href="#" class="forgot-link">Mot de passe oublié ?</a>
    </div>
    <!-- Submit -->
    <button class="btn-login" type="submit">Se connecter →</button>
  </form>
  <div class="or-divider"><span class="or-text">Nouveau sur CraftLink ?</span></div>
  <div class="register-link">
    Pas encore de compte ? <a href="#" id="open-register-modal" style="color:#217a3a;font-weight:600">Créez votre profil gratuitement</a>
  </div>
  <div class="form-footer">
    <span class="footer-brand">Projet CraftLink · 2025–2026</span>
    <div class="footer-lang">
      <button class="lang-btn active" type="button">FR</button>
      <button class="lang-btn" type="button">AR</button>
      <button class="lang-btn" type="button">EN</button>
    </div>
  </div>
</div>
<!-- Modal inscription -->
<div class="modal-overlay" id="register-modal" style="display:none;">
  <div class="modal">
    <div class="modal-header">
      <h2>Créer un compte</h2>
      <button type="button" class="modal-close" onclick="closeRegisterModal()">✕</button>
    </div>
    <form method="POST" action="" class="register-form">
      <div class="form-group">
        <label>Nom</label>
        <input type="text" name="reg_nom" minlength="2" maxlength="60" pattern="[A-Za-zÀ-ÖØ-öø-ÿ' -]+" required>
      </div>
      <div class="form-group">
        <label>Prénom</label>
        <input type="text" name="reg_prenom" minlength="2" maxlength="60" pattern="[A-Za-zÀ-ÖØ-öø-ÿ' -]+" required>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="reg_email" maxlength="120" required>
      </div>
      <div class="form-group">
        <label>Mot de passe</label>
        <input type="password" name="reg_password" minlength="8" maxlength="72" required>
      </div>
      <?php if (!empty($erreur_insc ?? '')): ?>
        <div class="error-msg" style="color:#b00; margin-bottom:10px; text-align:center;">
          <?php echo $erreur_insc; ?>
        </div>
      <?php endif; ?>
      <div class="modal-actions">
        <button type="button" class="btn-secondary" onclick="closeRegisterModal()">Annuler</button>
        <button type="submit" class="btn-primary" name="register">Créer mon compte</button>
      </div>
    </form>
  </div>
</div>
<script>
  function setRole(el, role) {
    document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
  }
  function togglePw() {
    const pw = document.getElementById('password');
    const btn = document.getElementById('pw-toggle');
    if (pw.type === 'password') { pw.type = 'text'; btn.textContent = '🙈'; }
    else { pw.type = 'password'; btn.textContent = '👁'; }
  }
  // Language toggle
  document.querySelectorAll('.lang-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.lang-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
    });
  });
  // Modal inscription
  const regModal = document.getElementById('register-modal');
  document.getElementById('open-register-modal').onclick = function(e) {
    e.preventDefault();
    regModal.style.display = 'block';
  }
  function closeRegisterModal() {
    regModal.style.display = 'none';
  }

  function bindAuthValidation() {
    const nameRegex = /^[A-Za-zÀ-ÖØ-öø-ÿ' -]{2,60}$/;

    document.querySelectorAll('.login-form, .register-form').forEach((form) => {
      form.addEventListener('submit', (event) => {
        const fields = form.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]');
        fields.forEach((field) => {
          field.value = field.value.trim();
          field.setCustomValidity('');
        });

        const nom = form.querySelector('input[name="reg_nom"]');
        if (nom && nom.value && !nameRegex.test(nom.value)) {
          nom.setCustomValidity('Nom invalide (lettres uniquement).');
        }

        const prenom = form.querySelector('input[name="reg_prenom"]');
        if (prenom && prenom.value && !nameRegex.test(prenom.value)) {
          prenom.setCustomValidity('Prénom invalide (lettres uniquement).');
        }

        const pwd = form.querySelector('input[name="password"], input[name="reg_password"]');
        if (pwd && pwd.value.length > 0 && pwd.value.length < 8) {
          pwd.setCustomValidity('Le mot de passe doit contenir au moins 8 caractères.');
        }

        if (!form.checkValidity()) {
          event.preventDefault();
          form.reportValidity();
        }
      });
    });
  }

  document.addEventListener('DOMContentLoaded', bindAuthValidation);
</script>
</body>
</html>
