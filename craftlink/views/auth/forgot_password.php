<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mot de passe oublié - CraftLink Tunisie</title>
  <link href="public/css/style.css" rel="stylesheet">
  <style>
    body { overflow:auto; }
    .reset-wrapper { min-height:100vh; display:flex; width:100%; }
  </style>
</head>
<body>
<div class="reset-wrapper">
  <div class="left-panel">
    <div class="brand">
      <div class="logo-ring"><img src="public/assets/logo.png" alt="Logo" class="logo-img"></div>
      <div class="brand-title">CraftLink Tunisie</div>
      <div class="brand-arabic">حرفة تونس</div>
      <div class="brand-sub">L'artisanat tunisien, à l'ère du numérique.</div>
    </div>
    <div class="divider-ornament"><div class="divider-diamond"></div></div>
    <div class="roles-title">Réinitialisation sécurisée</div>
    <div class="roles">
      <div class="role-card entrepreneur">✉ E-mail</div>
      <div class="role-card mentor">🔐 Code</div>
      <div class="role-card investisseur">🔑 Nouveau mot de passe</div>
    </div>
    <p class="left-quote">« Entrez votre e-mail, recevez votre code, puis choisissez un nouveau mot de passe. »</p>
  </div>

  <div class="right-panel" style="overflow-y:auto;">
    <div class="form-header">
      <div class="form-label-small">Mot de passe oublié</div>
      <div class="form-title">Réinitialiser votre mot de passe</div>
      <div class="form-subtitle">Suivez les 3 étapes pour récupérer l'accès.</div>
    </div>

    <!-- Étapes -->
    <div class="step-badges">
      <span class="step-badge <?= $step === 'email'    ? 'active' : '' ?>">1. E-mail</span>
      <span class="step-badge <?= $step === 'code'     ? 'active' : '' ?>">2. Code</span>
      <span class="step-badge <?= $step === 'password' ? 'active' : '' ?>">3. Nouveau mot de passe</span>
    </div>

    <?php if ($success): ?>
      <div class="alert-banner alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert-banner alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Étape 1 : email -->
    <?php if ($step === 'email'): ?>
    <form method="POST" action="index.php?page=forgot_password" id="form-fp" onsubmit="return validateFpEmail()">
      <input type="hidden" name="action" value="send_code">
      <div class="field-group">
        <label class="field-label" for="fp-email">Adresse e-mail</label>
        <div class="field-wrap">
          <span class="field-icon">✉</span>
          <!-- type="text", pas type="email" : validation JS uniquement -->
          <input type="text" id="fp-email" name="email"
                 placeholder="votre@email.com"
                 value="<?= htmlspecialchars($emailValue) ?>">
        </div>
        <div class="error-msg" id="fp-email-err">Veuillez entrer une adresse e-mail valide.</div>
      </div>
      <p class="helper-text">Nous allons envoyer un code de 6 chiffres à cette adresse.</p>
      <button class="btn-login" type="submit">Envoyer le code →</button>
    </form>
    <?php endif; ?>

    <!-- Étape 2 : code -->
    <?php if ($step === 'code'): ?>
    <form method="POST" action="index.php?page=forgot_password" id="form-code" onsubmit="return validateFpCode()">
      <input type="hidden" name="action" value="verify_code">
      <div class="field-group">
        <label class="field-label" for="fp-code">Code de vérification</label>
        <div class="field-wrap">
          <span class="field-icon">🔐</span>
          <input type="text" id="fp-code" name="code"
                 placeholder="Entrez le code à 6 chiffres" maxlength="6">
        </div>
        <div class="error-msg" id="fp-code-err">Veuillez entrer le code reçu par e-mail.</div>
      </div>
      <p class="helper-text">Un code a été envoyé à <strong><?= htmlspecialchars($emailValue) ?></strong>. Il expire dans 15 minutes.</p>
      <button class="btn-login" type="submit">Vérifier le code →</button>
    </form>
    <a class="back-link" href="index.php?page=forgot_password&restart=1">← Changer l'adresse e-mail</a>
    <?php endif; ?>

    <!-- Étape 3 : nouveau mot de passe -->
    <?php if ($step === 'password'): ?>
    <form method="POST" action="index.php?page=forgot_password" id="form-pw" onsubmit="return validateFpPassword()">
      <input type="hidden" name="action" value="change_password">
      <div class="field-group">
        <label class="field-label" for="fp-new">Nouveau mot de passe</label>
        <div class="field-wrap">
          <span class="field-icon">🔒</span>
          <input type="password" id="fp-new" name="new_password" placeholder="••••••••">
          <button type="button" class="toggle-pw" onclick="togglePw('fp-new','fp-new-toggle')" id="fp-new-toggle">👁</button>
        </div>
        <div class="error-msg" id="fp-new-err">Minimum 6 caractères.</div>
      </div>
      <div class="field-group">
        <label class="field-label" for="fp-conf">Confirmer le mot de passe</label>
        <div class="field-wrap">
          <span class="field-icon">🔒</span>
          <input type="password" id="fp-conf" name="confirm_password" placeholder="••••••••">
          <button type="button" class="toggle-pw" onclick="togglePw('fp-conf','fp-conf-toggle')" id="fp-conf-toggle">👁</button>
        </div>
        <div class="error-msg" id="fp-conf-err">Les mots de passe ne correspondent pas.</div>
      </div>
      <p class="helper-text">Après validation, votre mot de passe sera mis à jour.</p>
      <button class="btn-login" type="submit">Changer le mot de passe →</button>
    </form>
    <?php endif; ?>

    <a class="back-link" href="index.php?page=login">← Retour à la connexion</a>
  </div>
</div>

<script src="public/js/app.js"></script>
<script>
// Validation JS sans HTML5 pour le formulaire forgot_password
function validateFpEmail() {
  const val = document.getElementById('fp-email').value.trim();
  const err = document.getElementById('fp-email-err');
  const inp = document.getElementById('fp-email');
  if (!val || !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(val)) {
    inp.classList.add('error'); err.style.display = 'block'; return false;
  }
  inp.classList.remove('error'); err.style.display = 'none'; return true;
}
function validateFpCode() {
  const val = document.getElementById('fp-code').value.trim();
  const err = document.getElementById('fp-code-err');
  const inp = document.getElementById('fp-code');
  if (!val || val.length < 4) {
    inp.classList.add('error'); err.style.display = 'block'; return false;
  }
  inp.classList.remove('error'); err.style.display = 'none'; return true;
}
function validateFpPassword() {
  let ok = true;
  const newEl  = document.getElementById('fp-new');
  const confEl = document.getElementById('fp-conf');
  const newErr = document.getElementById('fp-new-err');
  const confErr= document.getElementById('fp-conf-err');
  if (!newEl.value || newEl.value.length < 6) {
    newEl.classList.add('error'); newErr.style.display = 'block'; ok = false;
  } else { newEl.classList.remove('error'); newErr.style.display = 'none'; }
  if (!confEl.value || confEl.value !== newEl.value) {
    confEl.classList.add('error'); confErr.style.display = 'block'; ok = false;
  } else { confEl.classList.remove('error'); confErr.style.display = 'none'; }
  return ok;
}
</script>
</body>
</html>
