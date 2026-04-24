/**
 * app.js — CraftLink FrontOffice
 * Validation côté client sans HTML5 (pas de required, type="email", pattern, etc.)
 */

// ── Utilitaires ──────────────────────────────────────────────────────────────

function showAlert(msg, type) {
  const banner = document.getElementById('alert-banner');
  if (!banner) return;
  banner.textContent = msg;
  banner.className = 'alert-banner alert-' + type;
  banner.style.display = 'block';
}

function hideAlert() {
  const banner = document.getElementById('alert-banner');
  if (banner) banner.style.display = 'none';
}

function setFieldError(fieldId, errId, show) {
  const field = document.getElementById(fieldId);
  const err   = document.getElementById(errId);
  if (!field) return;
  if (show) { field.classList.add('error'); if (err) err.style.display = 'block'; }
  else       { field.classList.remove('error'); if (err) err.style.display = 'none'; }
}

function clearAllErrors(fields) {
  fields.forEach(([fieldId, errId]) => setFieldError(fieldId, errId, false));
}

// ── Validation sans HTML5 ────────────────────────────────────────────────────

function validateEmail(value) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value.trim());
}

function validateMinLength(value, min) {
  return value.trim().length >= min;
}

function validateAlpha(value) {
  return /^[\u00C0-\u024F\u0600-\u06FFa-zA-Z\s\-]+$/.test(value.trim());
}

// ── Toggle mot de passe ──────────────────────────────────────────────────────

function togglePw(fieldId, btnId) {
  const pw  = document.getElementById(fieldId);
  const btn = document.getElementById(btnId);
  if (!pw) return;
  pw.type = pw.type === 'password' ? 'text' : 'password';
  if (btn) btn.textContent = pw.type === 'password' ? '👁' : '🙈';
}

// ── Rôle actif ───────────────────────────────────────────────────────────────

let currentRole = 'entrepreneur';

function setRole(el, role) {
  document.querySelectorAll('.role-tab').forEach(t => {
    t.classList.remove('active');
    t.style.outline = '';
  });
  el.classList.add('active');
  currentRole = role;
  hideAlert();
}

function markRoleError() {
  document.querySelectorAll('.role-tab').forEach(t => {
    t.style.outline = '2px solid #c0392b';
    t.style.outlineOffset = '-2px';
  });
}

function clearRoleError() {
  document.querySelectorAll('.role-tab').forEach(t => {
    t.style.outline = '';
    t.style.outlineOffset = '';
  });
}

// ── LOGIN ─────────────────────────────────────────────────────────────────────

async function handleLogin() {
  const emailEl = document.getElementById('email');
  const pwEl    = document.getElementById('password');
  const btn     = document.getElementById('btn-login');
  let valid = true;

  clearAllErrors([['email','email-err'],['password','pw-err']]);
  clearRoleError();
  hideAlert();

  // Validation email sans HTML5
  if (!emailEl.value || !validateEmail(emailEl.value)) {
    setFieldError('email', 'email-err', true);
    valid = false;
  }

  // Validation mot de passe
  if (!pwEl.value || !validateMinLength(pwEl.value, 1)) {
    setFieldError('password', 'pw-err', true);
    valid = false;
  }

  if (!valid) return;

  btn.textContent = '⏳ Vérification en cours…';
  btn.disabled = true;

  try {
    const fd = new FormData();
    fd.append('email',    emailEl.value.trim());
    fd.append('password', pwEl.value);
    fd.append('role',     currentRole);

    const res  = await fetch('index.php?page=login', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
      btn.textContent = '✓ Connecté !';
      btn.style.background = '#2E6B3E';
      sessionStorage.setItem('cl_nom',    data.nom);
      sessionStorage.setItem('cl_prenom', data.prenom);
      sessionStorage.setItem('cl_email',  emailEl.value.trim());
      sessionStorage.setItem('cl_role',   data.role);
      showAlert('✅ Bienvenue ' + data.prenom + ' ' + data.nom + ' ! Redirection…', 'success');
      setTimeout(() => { window.location.href = 'index.php?page=dashboard'; }, 1500);
    } else {
      btn.textContent = 'Se connecter →';
      btn.disabled = false;
      btn.style.background = '';
      showAlert('❌ ' + data.message, 'error');
      if (data.field === 'email')    setFieldError('email',    'email-err', true);
      if (data.field === 'password') setFieldError('password', 'pw-err', true);
      if (data.field === 'role')     markRoleError();
    }
  } catch (err) {
    btn.textContent = 'Se connecter →';
    btn.disabled = false;
    showAlert('⚠️ Erreur serveur. Vérifiez que XAMPP est démarré.', 'error');
  }
}

// ── REGISTER ──────────────────────────────────────────────────────────────────

async function handleRegister() {
  const fields = {
    nom:     document.getElementById('nom'),
    prenom:  document.getElementById('prenom'),
    email:   document.getElementById('email'),
    pw:      document.getElementById('password'),
    conf:    document.getElementById('confirm'),
    terms:   document.getElementById('terms'),
  };
  const btn = document.getElementById('btn-register');
  let valid = true;

  // Reset erreurs
  clearAllErrors([
    ['nom','nom-err'],['prenom','prenom-err'],['email','email-err'],
    ['password','pw-err'],['confirm','conf-err']
  ]);
  document.getElementById('terms-err').style.display = 'none';
  hideAlert();

  // Nom
  if (!fields.nom.value.trim()) {
    setFieldError('nom', 'nom-err', true);
    valid = false;
  } else if (!validateAlpha(fields.nom.value)) {
    setFieldError('nom', 'nom-err', true);
    document.getElementById('nom-err').textContent = 'Le nom ne doit contenir que des lettres.';
    valid = false;
  }

  // Prénom
  if (!fields.prenom.value.trim()) {
    setFieldError('prenom', 'prenom-err', true);
    valid = false;
  } else if (!validateAlpha(fields.prenom.value)) {
    setFieldError('prenom', 'prenom-err', true);
    document.getElementById('prenom-err').textContent = 'Le prénom ne doit contenir que des lettres.';
    valid = false;
  }

  // Email
  if (!fields.email.value.trim() || !validateEmail(fields.email.value)) {
    setFieldError('email', 'email-err', true);
    valid = false;
  }

  // Mot de passe
  if (!fields.pw.value || !validateMinLength(fields.pw.value, 6)) {
    setFieldError('password', 'pw-err', true);
    valid = false;
  }

  // Confirmation
  if (!fields.conf.value || fields.conf.value !== fields.pw.value) {
    setFieldError('confirm', 'conf-err', true);
    valid = false;
  }

  // CGU
  if (!fields.terms.checked) {
    document.getElementById('terms-err').style.display = 'block';
    valid = false;
  }

  if (!valid) return;

  btn.textContent = '⏳ Envoi du mail de vérification…';
  btn.disabled = true;

  try {
    const fd = new FormData();
    fd.append('nom',     fields.nom.value.trim());
    fd.append('prenom',  fields.prenom.value.trim());
    fd.append('email',   fields.email.value.trim());
    fd.append('password',fields.pw.value);
    fd.append('confirm', fields.conf.value);
    fd.append('role',    currentRole);

    const res  = await fetch('index.php?page=register', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
      btn.textContent = '✓ Mail envoyé';
      btn.style.background = '#2E6B3E';
      showAlert('✅ ' + data.message, 'success');
    } else {
      btn.textContent = 'Créer mon profil →';
      btn.disabled = false;
      showAlert('❌ ' + data.message, 'error');
      if (data.field) setFieldError(data.field, data.field + '-err', true);
    }
  } catch (err) {
    btn.textContent = 'Créer mon profil →';
    btn.disabled = false;
    showAlert('❌ Erreur serveur. Vérifiez que XAMPP est démarré.', 'error');
  }
}

// ── Jauge force du mot de passe ───────────────────────────────────────────────

function initPasswordStrength(inputId, fillId, labelId) {
  const input = document.getElementById(inputId);
  if (!input) return;
  input.addEventListener('input', function() {
    const val = this.value;
    let score = 0;
    if (val.length >= 6)          score++;
    if (val.length >= 10)         score++;
    if (/[A-Z]/.test(val))        score++;
    if (/[0-9]/.test(val))        score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
      { pct: '0%',   color: 'transparent', text: 'Force du mot de passe' },
      { pct: '25%',  color: '#c0392b',     text: 'Très faible' },
      { pct: '40%',  color: '#e67e22',     text: 'Faible' },
      { pct: '65%',  color: '#f1c40f',     text: 'Moyen' },
      { pct: '85%',  color: '#2E6B3E',     text: 'Fort' },
      { pct: '100%', color: '#27ae60',     text: 'Très fort ✓' },
    ];
    const l = levels[score] || levels[0];
    const fill  = document.getElementById(fillId);
    const label = document.getElementById(labelId);
    if (fill)  { fill.style.width = l.pct; fill.style.background = l.color; }
    if (label) { label.textContent = l.text; label.style.color = l.color === 'transparent' ? 'var(--text-muted)' : l.color; }
  });
}

// ── Modal CGU ────────────────────────────────────────────────────────────────

function openTermsModal(e) {
  e.preventDefault();
  document.getElementById('terms-modal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function closeTermsModal() {
  document.getElementById('terms-modal').style.display = 'none';
  document.body.style.overflow = '';
}
function acceptTermsFromModal() {
  document.getElementById('terms').checked = true;
  document.getElementById('terms-err').style.display = 'none';
  closeTermsModal();
}

// ── Init globale ──────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function() {
  // Touche Entrée → soumettre
  document.addEventListener('keydown', function(e) {
    if (e.key !== 'Enter') return;
    if (document.getElementById('terms-modal')?.style.display === 'flex') return;
    if (document.getElementById('btn-login'))    handleLogin();
    if (document.getElementById('btn-register')) handleRegister();
  });

  // Effacer erreurs au retape
  ['email','password','nom','prenom','confirm'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('input', function() {
      this.classList.remove('error');
      const err = document.getElementById(id + '-err');
      if (err) err.style.display = 'none';
      hideAlert();
    });
  });

  // Sélection langue
  document.querySelectorAll('.lang-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.lang-btn').forEach(b => b.classList.remove('active'));
      this.classList.add('active');
    });
  });

  // Password strength
  initPasswordStrength('password', 'pw-strength-fill', 'pw-strength-label');
});
