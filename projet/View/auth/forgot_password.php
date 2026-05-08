<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mot de passe oublie - CraftLink Tunisie</title>
  <link href="assets/css/login.css" rel="stylesheet">
  <style>
    body { overflow:auto; }
    .reset-wrapper { min-height:100vh; display:flex; width:100%; }
  </style>
</head>
<body>
<div class="reset-wrapper">
  <div class="left-panel">
    <div class="brand">
      <div class="logo-ring"><img src="assets/images/logo.png" alt="Logo" class="logo-img"></div>
      <div class="brand-title">CraftLink Tunisie</div>
      <div class="brand-arabic">حرفة تونس</div>
      <div class="brand-sub">L'artisanat tunisien, a l'ere du numerique.</div>
    </div>
    <div class="divider-ornament"><div class="divider-diamond"></div></div>
    <div class="roles-title">Reinitialisation securisee</div>
    <div class="roles">
      <div class="role-card entrepreneur">✉ E-mail</div>
      <div class="role-card mentor">🔐 Code</div>
      <div class="role-card investisseur">🔑 Nouveau mot de passe</div>
    </div>
    <p class="left-quote">« Entrez votre e-mail, recevez votre code, puis choisissez un nouveau mot de passe. »</p>
  </div>

  <div class="right-panel" style="overflow-y:auto;">
    <div class="form-header">
      <div class="form-label-small">Mot de passe oublie</div>
      <div class="form-title">Reinitialiser votre mot de passe</div>
      <div class="form-subtitle">Suivez les 3 etapes pour recuperer l'acces.</div>
    </div>

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

    <?php if ($step === 'email'): ?>
    <form method="POST" action="forgot_password.php" id="form-fp" onsubmit="return validateFpEmail()">
      <input type="hidden" name="action" value="send_code">
      <div class="field-group">
        <label class="field-label" for="fp-email">Adresse e-mail</label>
        <div class="field-wrap">
          <span class="field-icon">✉</span>
          <input type="text" id="fp-email" name="email"
                 placeholder="votre@email.com"
                 value="<?= htmlspecialchars($emailValue) ?>">
        </div>
        <div class="error-msg" id="fp-email-err">Veuillez entrer une adresse e-mail valide.</div>
      </div>
      <p class="helper-text">Nous allons envoyer un code de 6 chiffres a cette adresse.</p>
      <button class="btn-login" type="submit">Envoyer le code -></button>
    </form>
    <?php endif; ?>

    <?php if ($step === 'code'): ?>
    <form method="POST" action="forgot_password.php" id="form-code" onsubmit="return validateFpCode()">
      <input type="hidden" name="action" value="verify_code">
      <div class="field-group">
        <label class="field-label" for="fp-code">Code de verification</label>
        <div class="field-wrap">
          <span class="field-icon">🔐</span>
          <input type="text" id="fp-code" name="code"
                 placeholder="Entrez le code a 6 chiffres" maxlength="6">
        </div>
        <div class="error-msg" id="fp-code-err">Veuillez entrer le code recu par e-mail.</div>
      </div>
      <p class="helper-text">Un code a ete envoye a <strong><?= htmlspecialchars($emailValue) ?></strong>. Il expire dans 15 minutes.</p>
      <button class="btn-login" type="submit">Verifier le code -></button>
    </form>
    <a class="back-link" href="forgot_password.php?restart=1"><- Changer l'adresse e-mail</a>
    <?php endif; ?>

    <?php if ($step === 'password'): ?>
    <form method="POST" action="forgot_password.php" id="form-pw" onsubmit="return validateFpPassword()">
      <input type="hidden" name="action" value="change_password">
      <div class="field-group">
        <label class="field-label" for="fp-new">Nouveau mot de passe</label>
        <div class="field-wrap">
          <span class="field-icon">🔒</span>
          <input type="password" id="fp-new" name="new_password" placeholder="••••••••">
          <button type="button" class="toggle-pw" onclick="togglePw('fp-new','fp-new-toggle')" id="fp-new-toggle">👁</button>
        </div>
        <div class="error-msg" id="fp-new-err">Minimum 6 caracteres.</div>
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
      <p class="helper-text">Apres validation, votre mot de passe sera mis a jour.</p>
      <button class="btn-login" type="submit">Changer le mot de passe -></button>
    </form>
    <?php endif; ?>

    <a class="back-link" href="login.php"><- Retour a la connexion</a>
  </div>
</div>

<script src="assets/js/login.js"></script>
<script>
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
