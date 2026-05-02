/**
 * app.js - CraftLink FrontOffice
 */

const FACE_MODEL_URL = 'https://justadudewhohacks.github.io/face-api.js/models';
const faceState = {
  register: { stream: null, descriptor: null, loadingPromise: null, autoCaptureTimer: null, faceId: null },
  login: { stream: null, descriptor: null, loadingPromise: null, autoCaptureTimer: null, faceId: null, submitting: false },
};

function showAlert(msg, type) {
  const banner = document.getElementById('alert-banner');
  if (!banner) return;
  banner.textContent = msg;
  banner.className = 'alert-banner alert-' + type;
  banner.style.display = 'block';
}

function bindFaceFunctions() {
  window.startFaceEnrollment = startFaceEnrollment;
  window.captureFaceEnrollment = captureFaceEnrollment;
  window.startFaceLogin = startFaceLogin;
  window.handleFaceLogin = handleFaceLogin;
}

function hideAlert() {
  const banner = document.getElementById('alert-banner');
  if (banner) banner.style.display = 'none';
}

function setFieldError(fieldId, errId, show) {
  const field = document.getElementById(fieldId);
  const err = document.getElementById(errId);
  if (!field) return;
  if (show) {
    field.classList.add('error');
    if (err) err.style.display = 'block';
  } else {
    field.classList.remove('error');
    if (err) err.style.display = 'none';
  }
}

function clearAllErrors(fields) {
  fields.forEach(([fieldId, errId]) => setFieldError(fieldId, errId, false));
}

function validateEmail(value) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value.trim());
}

function validateMinLength(value, min) {
  return value.trim().length >= min;
}

function validateAlpha(value) {
  return /^[\u00C0-\u024F\u0600-\u06FFa-zA-Z\s\-]+$/.test(value.trim());
}

function getRecaptchaToken() {
  if (!window.grecaptcha || typeof grecaptcha.getResponse !== 'function') {
    return '';
  }

  return grecaptcha.getResponse();
}

function resetRecaptcha() {
  if (window.grecaptcha && typeof grecaptcha.reset === 'function') {
    grecaptcha.reset();
  }
}

function togglePw(fieldId, btnId) {
  const pw = document.getElementById(fieldId);
  const btn = document.getElementById(btnId);
  if (!pw) return;
  pw.type = pw.type === 'password' ? 'text' : 'password';
  if (btn) btn.textContent = pw.type === 'password' ? '👁' : '🙈';
}

let currentRole = 'entrepreneur';

function setRole(el, role) {
  document.querySelectorAll('.role-tab').forEach((tab) => {
    tab.classList.remove('active');
    tab.style.outline = '';
    tab.style.outlineOffset = '';
  });
  el.classList.add('active');
  currentRole = role;
  hideAlert();
}

function markRoleError() {
  document.querySelectorAll('.role-tab').forEach((tab) => {
    tab.style.outline = '2px solid #c0392b';
    tab.style.outlineOffset = '-2px';
  });
}

function clearRoleError() {
  document.querySelectorAll('.role-tab').forEach((tab) => {
    tab.style.outline = '';
    tab.style.outlineOffset = '';
  });
}

async function ensureFaceApiLoaded(mode) {
  if (!window.faceapi) {
    const status = document.getElementById(`rf-${mode}-status`);
    if (status) {
      status.textContent = 'Librairie faciale indisponible. Rechargez la page et autorisez Internet.';
    }
    throw new Error('La librairie de reconnaissance faciale est indisponible.');
  }

  if (!faceState[mode].loadingPromise) {
    faceState[mode].loadingPromise = Promise.all([
      faceapi.nets.tinyFaceDetector.loadFromUri(FACE_MODEL_URL),
      faceapi.nets.faceLandmark68Net.loadFromUri(FACE_MODEL_URL),
      faceapi.nets.faceRecognitionNet.loadFromUri(FACE_MODEL_URL),
    ]);
  }

  return faceState[mode].loadingPromise;
}

async function startFaceCamera(mode) {
  const video = document.getElementById(`rf-${mode}-video`);
  const status = document.getElementById(`rf-${mode}-status`);
  if (!video || !status) return;

  status.textContent = 'Chargement de la reconnaissance faciale...';
  await ensureFaceApiLoaded(mode);

  if (faceState[mode].stream) {
    video.style.display = 'block';
    status.textContent = 'Camera deja activee. Placez votre visage dans le cadre.';
    scheduleFaceAutoCapture(mode);
    return;
  }

  try {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      status.textContent = 'Votre navigateur ne supporte pas l acces camera.';
      throw new Error('Camera non supportee');
    }

    const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
    faceState[mode].stream = stream;
    video.srcObject = stream;
    video.style.display = 'block';
    status.textContent = 'Camera activee. Placez votre visage bien en face.';
    scheduleFaceAutoCapture(mode);
  } catch (err) {
    status.textContent = 'Impossible d acceder a la camera.';
    throw err;
  }
}

function computeFaceId(descriptor) {
  if (!Array.isArray(descriptor) || descriptor.length === 0) return '';
  let hash = 0;
  for (let i = 0; i < descriptor.length; i++) {
    const scaled = Math.round(Number(descriptor[i]) * 1000000);
    hash = ((hash << 5) - hash + scaled) | 0;
  }

  return 'FACE-' + Math.abs(hash).toString(16).toUpperCase().padStart(8, '0');
}

function updateFaceIdentity(mode, descriptor) {
  const faceId = computeFaceId(descriptor);
  const faceIdField = document.getElementById(`rf-${mode}-face-id`);
  const faceIdLabel = document.getElementById(`rf-${mode}-face-id-label`);

  faceState[mode].faceId = faceId;
  if (faceIdField) faceIdField.value = faceId;
  if (faceIdLabel) faceIdLabel.textContent = faceId ? `ID visage : ${faceId}` : 'ID visage : non detecte.';
}

function clearFaceIdentity(mode) {
  const hidden = document.getElementById(`rf-${mode}-descriptor`);
  const faceIdField = document.getElementById(`rf-${mode}-face-id`);
  const faceIdLabel = document.getElementById(`rf-${mode}-face-id-label`);

  faceState[mode].descriptor = null;
  faceState[mode].faceId = null;
  if (hidden) hidden.value = '';
  if (faceIdField) faceIdField.value = '';
  if (faceIdLabel) faceIdLabel.textContent = 'ID visage : non detecte.';
}

function scheduleFaceAutoCapture(mode) {
  if (faceState[mode].autoCaptureTimer) {
    clearTimeout(faceState[mode].autoCaptureTimer);
  }

  faceState[mode].autoCaptureTimer = setTimeout(async () => {
    try {
      const descriptor = await captureFaceDescriptor(mode, true);
      if (descriptor && mode === 'login') {
        await submitFaceLogin(descriptor);
        return;
      }

      if (!descriptor) {
        scheduleFaceAutoCapture(mode);
      }
    } catch (_) {
      scheduleFaceAutoCapture(mode);
    }
  }, 900);
}

async function captureFaceDescriptor(mode, silent) {
  const video = document.getElementById(`rf-${mode}-video`);
  const hidden = document.getElementById(`rf-${mode}-descriptor`);
  const status = document.getElementById(`rf-${mode}-status`);
  if (!video || !hidden || !status) return null;

  await ensureFaceApiLoaded(mode);
  if (!faceState[mode].stream) {
    await startFaceCamera(mode);
  }

  status.textContent = 'Analyse de votre visage...';
  const detection = await faceapi
    .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
    .withFaceLandmarks()
    .withFaceDescriptor();

  if (!detection) {
    status.textContent = silent ? 'Recherche automatique du visage...' : 'Aucun visage detecte. Veuillez reessayer.';
    clearFaceIdentity(mode);
    return null;
  }

  const descriptor = Array.from(detection.descriptor);
  faceState[mode].descriptor = descriptor;
  hidden.value = JSON.stringify(descriptor);
  updateFaceIdentity(mode, descriptor);
  status.textContent = silent ? 'Visage detecte automatiquement.' : 'Visage capture avec succes.';
  if (mode === 'login' && silent && !faceState.login.submitting) {
    setTimeout(() => submitFaceLogin(descriptor), 0);
  }
  return descriptor;
}

async function startFaceEnrollment() {
  try {
    await startFaceCamera('register');
  } catch (_) {
    showAlert('Impossible d activer la camera pour l inscription faciale.', 'error');
  }
}

async function captureFaceEnrollment() {
  try {
    const descriptor = await captureFaceDescriptor('register', false);
    if (!descriptor) {
      showAlert('Aucun visage detecte. Veuillez reessayer.', 'error');
      return;
    }
    showAlert('Visage enregistre pour l inscription. ID visage genere.', 'success');
  } catch (_) {
    showAlert('Erreur pendant la capture du visage.', 'error');
  }
}

async function startFaceLogin() {
  try {
    clearAllErrors([['email', 'email-err'], ['password', 'pw-err']]);
    clearRoleError();
    hideAlert();
    await startFaceCamera('login');
  } catch (_) {
    showAlert('Impossible d activer la camera pour la connexion faciale.', 'error');
  }
}

async function handleFaceLogin() {
  const descriptor = await captureFaceDescriptor('login', false);
  if (!descriptor) {
    showAlert('Visage non detecte. Veuillez reessayer.', 'error');
    return;
  }

  await submitFaceLogin(descriptor);
}

async function submitFaceLogin(descriptor) {
  const emailEl = document.getElementById('email');
  const status = document.getElementById('rf-login-status');

  clearAllErrors([['email', 'email-err'], ['password', 'pw-err']]);
  clearRoleError();
  hideAlert();

  if (faceState.login.submitting) return;

  faceState.login.submitting = true;
  if (status) status.textContent = 'Visage detecte. Connexion automatique...';

  try {
    const fd = new FormData();
    if (emailEl && emailEl.value.trim()) {
      fd.append('email', emailEl.value.trim());
    }
    fd.append('role', currentRole);
    if (faceState.login.faceId) {
      fd.append('face_id', faceState.login.faceId);
    }
    fd.append('face_descriptor', JSON.stringify(descriptor));

    const res = await fetch('index.php?page=login_face', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
      showAlert('Connexion faciale reussie. Redirection...', 'success');
      setTimeout(() => {
        if (data.role === 'admin') {
          window.location.href = 'admin/index.php';
          return;
        }
        window.location.href = 'index.php?page=dashboard';
      }, 1200);
      return;
    }

    faceState.login.submitting = false;
    showAlert(data.message, 'error');
    if (status) status.textContent = 'Connexion faciale refusee. Corrigez les informations puis reactivez la camera.';
    if (data.field === 'email') setFieldError('email', 'email-err', true);
    if (data.field === 'role') markRoleError();
  } catch (_) {
    faceState.login.submitting = false;
    if (status) status.textContent = 'Erreur serveur pendant la connexion faciale.';
    showAlert('Erreur serveur pendant la connexion faciale.', 'error');
  }
}

async function handleLogin() {
  const emailEl = document.getElementById('email');
  const pwEl = document.getElementById('password');
  const btn = document.getElementById('btn-login');
  let valid = true;

  clearAllErrors([['email', 'email-err'], ['password', 'pw-err']]);
  clearRoleError();
  hideAlert();

  if (!emailEl.value || !validateEmail(emailEl.value)) {
    setFieldError('email', 'email-err', true);
    valid = false;
  }

  if (!pwEl.value || !validateMinLength(pwEl.value, 1)) {
    setFieldError('password', 'pw-err', true);
    valid = false;
  }

  if (!valid) return;

  btn.textContent = 'Verification en cours...';
  btn.disabled = true;

  try {
    const fd = new FormData();
    fd.append('email', emailEl.value.trim());
    fd.append('password', pwEl.value);
    fd.append('role', currentRole);
    fd.append('g-recaptcha-response', getRecaptchaToken());

    const res = await fetch('index.php?page=login', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
      btn.textContent = 'Connecte !';
      btn.style.background = '#2E6B3E';
      sessionStorage.setItem('cl_nom', data.nom);
      sessionStorage.setItem('cl_prenom', data.prenom);
      sessionStorage.setItem('cl_email', emailEl.value.trim());
      sessionStorage.setItem('cl_role', data.role);
      showAlert('Bienvenue ' + data.prenom + ' ' + data.nom + ' ! Redirection...', 'success');
      setTimeout(() => {
        if (data.role === 'admin') {
          window.location.href = 'admin/index.php';
          return;
        }
        window.location.href = 'index.php?page=dashboard';
      }, 1500);
    } else {
      btn.textContent = 'Se connecter ->';
      btn.disabled = false;
      btn.style.background = '';
      resetRecaptcha();
      showAlert(data.message, 'error');
      if (data.field === 'email') setFieldError('email', 'email-err', true);
      if (data.field === 'password') setFieldError('password', 'pw-err', true);
      if (data.field === 'role') markRoleError();
    }
  } catch (_) {
    btn.textContent = 'Se connecter ->';
    btn.disabled = false;
    resetRecaptcha();
    showAlert('Erreur serveur. Verifiez que XAMPP est demarre.', 'error');
  }
}

async function handleRegister() {
  const fields = {
    nom: document.getElementById('nom'),
    prenom: document.getElementById('prenom'),
    email: document.getElementById('email'),
    pw: document.getElementById('password'),
    conf: document.getElementById('confirm'),
    terms: document.getElementById('terms'),
    face: document.getElementById('rf-register-descriptor'),
  };
  const btn = document.getElementById('btn-register');
  let valid = true;

  clearAllErrors([
    ['nom', 'nom-err'],
    ['prenom', 'prenom-err'],
    ['email', 'email-err'],
    ['password', 'pw-err'],
    ['confirm', 'conf-err'],
  ]);

  const termsErr = document.getElementById('terms-err');
  if (termsErr) termsErr.style.display = 'none';
  hideAlert();

  if (!fields.nom.value.trim()) {
    setFieldError('nom', 'nom-err', true);
    valid = false;
  } else if (!validateAlpha(fields.nom.value)) {
    setFieldError('nom', 'nom-err', true);
    document.getElementById('nom-err').textContent = 'Le nom ne doit contenir que des lettres.';
    valid = false;
  }

  if (!fields.prenom.value.trim()) {
    setFieldError('prenom', 'prenom-err', true);
    valid = false;
  } else if (!validateAlpha(fields.prenom.value)) {
    setFieldError('prenom', 'prenom-err', true);
    document.getElementById('prenom-err').textContent = 'Le prenom ne doit contenir que des lettres.';
    valid = false;
  }

  if (!fields.email.value.trim() || !validateEmail(fields.email.value)) {
    setFieldError('email', 'email-err', true);
    valid = false;
  }

  if (!fields.pw.value || !validateMinLength(fields.pw.value, 6)) {
    setFieldError('password', 'pw-err', true);
    valid = false;
  }

  if (!fields.conf.value || fields.conf.value !== fields.pw.value) {
    setFieldError('confirm', 'conf-err', true);
    valid = false;
  }

  if (!fields.terms.checked) {
    if (termsErr) termsErr.style.display = 'block';
    valid = false;
  }

  if (!fields.face.value) {
    showAlert('Veuillez capturer votre visage avant de creer le compte.', 'error');
    valid = false;
  }

  if (!valid) return;

  btn.textContent = 'Envoi du mail de verification...';
  btn.disabled = true;

  try {
    const fd = new FormData();
    fd.append('nom', fields.nom.value.trim());
    fd.append('prenom', fields.prenom.value.trim());
    fd.append('email', fields.email.value.trim());
    fd.append('password', fields.pw.value);
    fd.append('confirm', fields.conf.value);
    fd.append('role', currentRole);
    fd.append('face_descriptor', fields.face.value);
    fd.append('g-recaptcha-response', getRecaptchaToken());

    const res = await fetch('index.php?page=register', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
      btn.textContent = 'Mail envoye';
      btn.style.background = '#2E6B3E';
      const faceIdLabel = document.getElementById('rf-register-face-id-label');
      if (faceIdLabel && data.face_id) {
        faceIdLabel.textContent = 'ID visage : ' + data.face_id;
      }
      showAlert(data.message, 'success');
    } else {
      btn.textContent = 'Creer mon profil ->';
      btn.disabled = false;
      resetRecaptcha();
      showAlert(data.message, 'error');
      if (data.field) setFieldError(data.field, data.field + '-err', true);
    }
  } catch (_) {
    btn.textContent = 'Creer mon profil ->';
    btn.disabled = false;
    resetRecaptcha();
    showAlert('Erreur serveur. Verifiez que XAMPP est demarre.', 'error');
  }
}

function initPasswordStrength(inputId, fillId, labelId) {
  const input = document.getElementById(inputId);
  if (!input) return;

  input.addEventListener('input', function () {
    const val = this.value;
    let score = 0;
    if (val.length >= 6) score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
      { pct: '0%', color: 'transparent', text: 'Force du mot de passe' },
      { pct: '25%', color: '#c0392b', text: 'Tres faible' },
      { pct: '40%', color: '#e67e22', text: 'Faible' },
      { pct: '65%', color: '#f1c40f', text: 'Moyen' },
      { pct: '85%', color: '#2E6B3E', text: 'Fort' },
      { pct: '100%', color: '#27ae60', text: 'Tres fort' },
    ];

    const level = levels[score] || levels[0];
    const fill = document.getElementById(fillId);
    const label = document.getElementById(labelId);
    if (fill) {
      fill.style.width = level.pct;
      fill.style.background = level.color;
    }
    if (label) {
      label.textContent = level.text;
      label.style.color = level.color === 'transparent' ? 'var(--text-muted)' : level.color;
    }
  });
}

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

document.addEventListener('DOMContentLoaded', function () {
  bindFaceFunctions();

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') return;
    if (document.getElementById('terms-modal')?.style.display === 'flex') return;
    if (document.getElementById('btn-login')) handleLogin();
    if (document.getElementById('btn-register')) handleRegister();
  });

  ['email', 'password', 'nom', 'prenom', 'confirm'].forEach((id) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('input', function () {
      this.classList.remove('error');
      const err = document.getElementById(id + '-err');
      if (err) err.style.display = 'none';
      hideAlert();
    });
  });

  document.querySelectorAll('.lang-btn').forEach((btn) => {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.lang-btn').forEach((b) => b.classList.remove('active'));
      this.classList.add('active');
    });
  });

  initPasswordStrength('password', 'pw-strength-fill', 'pw-strength-label');
});
